from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List
from ortools.constraint_solver import pywrapcp, routing_enums_pb2
import requests

app = FastAPI()

class Location(BaseModel):
    lat: float
    lng: float

class RouteRequest(BaseModel):
    locations: List[Location]
    depot_index: int = 0
    profile: str = "driving"

def get_osrm_distance_matrix(locations: List[Location], profile: str):
    coords = ";".join(f"{loc.lng},{loc.lat}" for loc in locations)
    url = f"https://router.project-osrm.org/table/v1/{profile}/{coords}?annotations=distance"
    response = requests.get(url)
    if response.status_code != 200:
        raise HTTPException(status_code=500, detail=response.text)
    data = response.json()
    if "distances" not in data:
        raise HTTPException(status_code=500, detail="Invalid OSRM response")
    return data["distances"]

@app.post("/optimize")
def optimize_route(data: RouteRequest):
    if len(data.locations) < 2:
        raise HTTPException(status_code=400, detail="At least 2 locations required")

    # 1. Get raw distance matrix
    raw_distances = get_osrm_distance_matrix(data.locations, data.profile)
    num_locations = len(data.locations)

    # 2. Add Dummy Node to allow "One-Way" (No return to depot)
    # The dummy node (index: num_locations) has 0 distance to/from everyone
    size = num_locations + 1
    matrix = [[0] * size for _ in range(size)]
    for i in range(num_locations):
        for j in range(num_locations):
            # Scale by 10 to maintain precision during float -> int conversion
            matrix[i][j] = int(raw_distances[i][j] * 10)

    # 3. OR-Tools Setup
    # Start at depot_index, end at the dummy node (index: num_locations)
    manager = pywrapcp.RoutingIndexManager(size, 1, [data.depot_index], [num_locations])
    routing = pywrapcp.RoutingModel(manager)

    def distance_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return matrix[from_node][to_node]

    transit_callback_index = routing.RegisterTransitCallback(distance_callback)
    routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

    search_parameters = pywrapcp.DefaultRoutingSearchParameters()
    search_parameters.first_solution_strategy = (
        routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
    )
    search_parameters.local_search_metaheuristic = (
        routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
    )
    search_parameters.time_limit.seconds = 2

    solution = routing.SolveWithParameters(search_parameters)

    if not solution:
        raise HTTPException(status_code=500, detail="No solution found")

    # 4. Extract optimized order (Excluding the Dummy Node)
    index = routing.Start(0)
    optimized_route = []
    while not routing.IsEnd(index):
        node_index = manager.IndexToNode(index)
        if node_index < num_locations: # Don't include dummy node in result
            optimized_route.append(node_index)
        index = solution.Value(routing.NextVar(index))

    # 5. Get real distance & duration for final ordered route via OSRM
    ordered_coords = [f"{data.locations[i].lng},{data.locations[i].lat}" for i in optimized_route]
    coords_string = ";".join(ordered_coords)
    route_url = f"https://router.project-osrm.org/route/v1/{data.profile}/{coords_string}?overview=false"

    route_response = requests.get(route_url)
    if route_response.status_code != 200:
        raise HTTPException(status_code=500, detail=route_response.text)

    route_data = route_response.json()
    real_distance = route_data["routes"][0]["distance"]
    real_duration = route_data["routes"][0]["duration"]

    # 6. Final Result (Original Object Structure)
    return {
        "route": optimized_route,
        "total_distance": int(real_distance)/1000,
        "total_time": (real_duration)/60
    }