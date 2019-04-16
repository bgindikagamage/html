<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once('b2b_mappings.php');
require_once('common.php');

generateReport(
    $mapping,
    function ($fp, $aggregatedResult, $headersArray, $executionTime) {
        $pageViews = 0;
        $requisitionsCount = 0;
        foreach ($aggregatedResult as $row) {
            if ($row['is_storefront']) {
                $pageViews += count($row['time']);
            }
            if ($row['is_requisition']) {
                $requisitionsCount += count($row['time']);
            }
        }
        fputcsv($fp, ['Requisitions Per Hour:', round($requisitionsCount / $executionTime * 3600000, 2)]);
        fputcsv($fp, ['Page Views Per Hour:', round($pageViews / $executionTime * 3600000, 2)]);
        fputcsv($fp, ['Test Duration, s:', round($executionTime / 1000)]);
        fputcsv($fp, ['']);
        fputcsv($fp, ['']);
        array_splice($headersArray, 3, 0, 'Is Requisition?');
        fputcsv($fp, $headersArray);
    },
    function ($row, &$rowData) {
        array_splice($rowData, 3, 0, $row['is_requisition'] ? 'Yes' : 'No');
    }
);
