<?php

/*
 * get list from http://twbusiness.nat.gov.tw/succeed.do
 */

$tmpPath = __DIR__ . '/tmp/succeed';
if (!file_exists($tmpPath)) {
    mkdir($tmpPath, 0777, true);
}

$result = array();
for ($i = 1; $i <= 5; $i ++) {
    $listUrl = 'http://twbusiness.nat.gov.tw/succeed.do?doName=succeed&keyword=&pageSize=10&pageNo=' . $i;
    $listFile = $tmpPath . '/list_' . md5($listUrl);
    if (!file_exists($listFile)) {
        file_put_contents($listFile, file_get_contents($listUrl));
    }
    $listContent = file_get_contents($listFile);
    $pos = strpos($listContent, '<table class="content"');
    if (false !== $pos) {
        $listContent = substr($listContent, $pos, strpos($listContent, '</table>', $pos) - $pos);
        $lines = explode('</tr>', $listContent);
        foreach ($lines AS $line) {
            $cols = explode('</td>', $line);
            if (count($cols) === 4) {
                $time = strtotime(trim(strip_tags($cols[2])));
                $cols[1] = explode('href="', $cols[1]);
                if (!isset($result[$time])) {
                    $result[$time] = array();
                }
                $result[$time][] = array(
                    date('Y-m-d', $time),
                    trim(strip_tags($cols[0])),
                    'http://twbusiness.nat.gov.tw/' . substr($cols[1][1], 0, strpos($cols[1][1], '"')),
                );
            }
        }
    } else {
        unlink($listFile);
    }
}
ksort($result);
$targetPath = __DIR__ . '/succeed';
if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}
$fh = fopen(__DIR__ . '/succeed.csv', 'w');
foreach ($result AS $t => $items) {
    $counter = 0;
    foreach ($items AS $item) {
        ++$counter;
        $p = pathinfo($item[2]);
        $filename = date('Ymd', $t) . '-' . $counter . '.' . $p['extension'];
        $targetFile = "{$targetPath}/{$filename}";
        if (!file_exists($targetFile)) {
            file_put_contents($targetFile, file_get_contents($item[2]));
        }
        $item[] = $filename;
        fputcsv($fh, $item);
    }
}
fclose($fh);
