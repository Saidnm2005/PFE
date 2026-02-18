from fastapi import FastAPI
from pydantic import BaseModel
from typing import List
from ortools.constraint_solver import pywrapcp, routing_enums_pb2
import math

app = FastAPI()

class Location(BaseModel):
    #id: int | str = 0    # Default to 0 for the depot
    #name: str   # Default name
    lat: float
    lng: float

class RouteRequest(BaseModel):
    locations: List[Location]
    depot_index: int = 0

def haversine(lat1, lon1, lat2, lon2):
    R = 6371
    dLat = math.radians(lat2 - lat1)
    dLon = math.radians(lon2 - lon1)
    a = math.sin(dLat/2)**2 + \
        math.cos(math.radians(lat1)) * \
        math.cos(math.radians(lat2)) * \
        math.sin(dLon/2)**2
    return int(1000 * 2 * R * math.asin(math.sqrt(a)))

def create_distance_matrix(locations):
    matrix = []
    for from_node in locations:
        row = []
        for to_node in locations:
            row.append(
                haversine(
                    from_node.lat, from_node.lng,
                    to_node.lat, to_node.lng
                )
            )
        matrix.append(row)
    return matrix
@app.post("/optimize")
def optimize_route(data: RouteRequest):

    distance_matrix = create_distance_matrix(data.locations)

    manager = pywrapcp.RoutingIndexManager(
        len(distance_matrix),
        1,
        data.depot_index
    )

    routing = pywrapcp.RoutingModel(manager)

    def distance_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return distance_matrix[from_node][to_node]

    transit_callback_index = routing.RegisterTransitCallback(distance_callback)
    routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

    search_parameters = pywrapcp.DefaultRoutingSearchParameters()
    search_parameters.first_solution_strategy = (
        routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
    )
    search_parameters.local_search_metaheuristic = (
        routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
    )
    search_parameters.time_limit.seconds = 5

    solution = routing.SolveWithParameters(search_parameters)

    if not solution:
        return {"error": "No solution found"}

    index = routing.Start(0)
    optimized_route = []

    while not routing.IsEnd(index):
        node_index = manager.IndexToNode(index)
        optimized_route.append(node_index)
        index = solution.Value(routing.NextVar(index))

    optimized_route.append(manager.IndexToNode(index))

    return {
        "route": optimized_route,
        "total_distance_meters": solution.ObjectiveValue()
    }
