<?php
declare(strict_types=1);
function qbnox_json_decode(string $j): array { try { return json_decode($j,true,512,JSON_THROW_ON_ERROR);} catch (Throwable $e){ return []; }}