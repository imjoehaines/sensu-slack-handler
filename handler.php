<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Maknz\Slack\Client;

// load environment variables
$dotenv = new Dotenv(__DIR__);
$dotenv->load();
$dotenv->required('SLACK_WEBHOOK_URL')->notEmpty();
$dotenv->required('SLACK_CHANNEL');

$colours = ['good', 'warning', 'danger'];
$titles = ['Yay!', 'Uh oh…', ':rotating_light: SOMETHING BROKE :rotating_light:'];
$messages = ['`CheckResult::ok`', '`CheckResult::warning`', '`CheckResult::critical`'];
$icons = [':green_heart:', ':warning:', ':rotating_light:'];

$rawSensuOutput = fgets(STDIN);
$sensuOutput = json_decode($rawSensuOutput, true);

$exitCode = $sensuOutput['check']['status'];

$colour = in_array($exitCode, array_keys($colours))
    ? $colours[$exitCode]
    : '#663399';

$title = in_array($exitCode, array_keys($titles))
    ? $titles[$exitCode]
    : 'The sensor broke!';

$message = in_array($exitCode, array_keys($messages))
    ? $messages[$exitCode]
    : 'Unknown exit code from the sensor: "' . json_encode($exitCode) . '"';

$icon = in_array($exitCode, array_keys($icons))
    ? $icons[$exitCode]
    : ':interrobang:';

$channel = getenv('SLACK_CHANNEL') ?: '#sensu_testing';

$clientOptions = ['username' => 'Sensu', 'icon' => $icon, 'channel' => $channel];

$client = new Client(getenv('SLACK_WEBHOOK_URL'), $clientOptions);
$client->attach([
    'fallback' => $sensuOutput['check']['name'],
    'title' => $title,
    'text' => $message,
    'color' => $colour,
    'mrkdwn_in' => ['text'],
    'fields' => [
        [
            'title' => 'Sensor',
            'value' => $sensuOutput['check']['name'],
            'short' => true,
        ],
        [
            'title' => 'Client',
            'value' => $sensuOutput['client']['name'],
            'short' => true,
        ],
        [
            'title' => 'Output',
            'value' => $sensuOutput['check']['output'],
        ]
    ],
])->send();
