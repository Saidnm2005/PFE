<?php

namespace App\Services;

class NearestNeighborService
{
    public function optimize(array $stations, array $start)
    {
        $route = [];
        $current = $start;
        $totalDistance = 0;
        $position = 1;

        while (!empty($stations)) {
            $nearestIndex = null;
            $minDistance = INF;

            foreach ($stations as $index => $station) {
                $distance = $this->distance(
                    $current['lat'], $current['lng'],
                    $station['lat'], $station['lng']
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $station = $stations[$nearestIndex];
            $station['distance'] = $minDistance;
            $station['position'] = $position++;

            $route[] = $station;
            $totalDistance += $minDistance;

            $current = $station;
            unset($stations[$nearestIndex]);
            $stations = array_values($stations);
        }

        return [
            'route' => $route,
            'total_distance' => $totalDistance
        ];
    }

    private function distance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) *
             cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}
