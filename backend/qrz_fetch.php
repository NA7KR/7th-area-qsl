<?php
function fetchQRZData($callsign, $config) {
    $initialUrl = "https://xmldata.qrz.com/xml/current/";
    $params = [
        'username' => $config['qrz_api']['callsign'],
        'password' => $config['qrz_api']['key']
    ];

    // Get session key
    $ch = curl_init($initialUrl . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['error' => 'Failed to connect to QRZ: ' . curl_error($ch)];
    }
    
    $xml = simplexml_load_string($response);
    if (!$xml) {
        return ['error' => 'Failed to parse QRZ response'];
    }

    $sessionKey = (string)$xml->Session->Key;
    if (empty($sessionKey)) {
        return ['error' => 'No session key received from QRZ'];
    }

    // Get callsign data
    $params = [
        's' => $sessionKey,
        'callsign' => $callsign
    ];
    
    $ch = curl_init($initialUrl . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $xml = simplexml_load_string($response);
    if (!$xml) {
        return ['error' => 'Failed to parse callsign data'];
    }

    if (isset($xml->Session->Error)) {
        return ['error' => (string)$xml->Session->Error];
    }

    // Map QRZ fields to our database fields
    $data = [];
    $mapping = [
        'fname' => 'first_name',
        'name' => 'last_name',
        'addr1' => 'address',
        'addr2' => 'city',
        'state' => 'state',
        'zip' => 'zip',
        'email' => 'email',
        'class' => 'class',
        'efdate' => 'date_start',
        'expdate' => 'date_exp',
        'born' => 'born'
    ];

    foreach ($mapping as $qrz => $db) {
        if (isset($xml->Callsign->$qrz)) {
            $data[$db] = (string)$xml->Callsign->$qrz;
        }
    }

    // Clean up first name (remove initials)
    if (isset($data['first_name'])) {
        $data['first_name'] = preg_replace('/(?:\s[A-Z]\.?)+$/', '', $data['first_name']);
    }

    // Convert license class
    if (isset($data['class'])) {
        $classMap = [
            'E' => 'Extra',
            'G' => 'General',
            'T' => 'Technician',
            'A' => 'Advanced',
            'C' => 'Club'
        ];
        $data['class'] = $classMap[$data['class']] ?? $data['class'];
    }

    return $data;
}