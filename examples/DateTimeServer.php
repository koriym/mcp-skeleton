<?php

namespace McpSkeleton\Examples;

use McpSkeleton\SkeletonMcpServer;
use Override;

/**
 * A simple date/time information MCP server example
 * 
 * This example demonstrates basic MCP server implementation with useful date/time tools.
 * 
 * @psalm-type SunInfo = array{
 *     sunrise: int,
 *     sunset: int,
 *     transit: int,
 *     civil_twilight_begin: int,
 *     civil_twilight_end: int,
 *     nautical_twilight_begin: int,
 *     nautical_twilight_end: int,
 *     astronomical_twilight_begin: int,
 *     astronomical_twilight_end: int
 * }
 * @psalm-type ToolArguments = array{
 *     latitude?: float|int,
 *     longitude?: float|int,
 *     timezone?: string
 * }
 * @extends SkeletonMcpServer<ToolArguments>
 */
class DateTimeServer extends SkeletonMcpServer
{
    protected const SERVER_NAME = 'datetime-mcp-server';
    protected const SERVER_VERSION = '1.0.0';

    #[Override]
    protected function initializeTools(): void
    {
        $this->addTool(
            'sunset_time',
            'Get sunset time for today',
            $this->createInputSchema([
                'latitude' => $this->createProperty('number', 'Latitude', 35.6762),
                'longitude' => $this->createProperty('number', 'Longitude', 139.6503)
            ])
        );

        $this->addTool(
            'days_remaining',
            'Get remaining days in current year',
            $this->createInputSchema()
        );

        $this->addTool(
            'current_time',
            'Get current time with timezone',
            $this->createInputSchema([
                'timezone' => $this->createProperty('string', 'Timezone', 'UTC')
            ])
        );
    }

    /**
     * @param ToolArguments $arguments
     */
    #[Override]
    protected function executeToolCall(string $toolName, array $arguments): string
    {
        switch ($toolName) {
            case 'sunset_time':
                $lat = $arguments['latitude'] ?? 35.6762;
                $lng = $arguments['longitude'] ?? 139.6503;
                $info = date_sun_info(time(), $lat, $lng);
                $sunset = date('H:i:s', $info['sunset']);
                return "Sunset time: {$sunset} (lat: {$lat}, lng: {$lng})";

            case 'days_remaining':
                $today = new \DateTime();
                $endOfYear = new \DateTime('December 31');
                $diff = $today->diff($endOfYear);
                return "Days remaining in {$today->format('Y')}: {$diff->days} days";

            case 'current_time':
                $tz = $arguments['timezone'] ?? 'UTC';
                try {
                    $dt = new \DateTime('now', new \DateTimeZone($tz));
                    return "Current time: {$dt->format('Y-m-d H:i:s T')}";
                } catch (\Exception $e) {
                    return "Error: Invalid timezone '{$tz}'";
                }

            default:
                throw new \Exception("Unknown tool: {$toolName}");
        }
    }
}