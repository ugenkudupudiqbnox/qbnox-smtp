<?php
declare(strict_types=1);
function qbnox_json_decode(string $json): array
{
    try {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return [];
    }
}
