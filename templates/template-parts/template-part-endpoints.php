<?php

$base_url = site_url() . '/wp-json/api/v1';

?>

<h4 class="common-title">Endpoints</h4>

<div class="endpoints-wrapper">
    <table class="endpoints-table">
        <thead>
            <tr>
                <th>Endpoint</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $base_url ?>/sync-countries</td>
                <td>Sync Countries</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
            <tr>
                <td><?= $base_url ?>/sync-centers</td>
                <td>Sync Centers</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
            <tr>
                <td><?= $base_url ?>/get-products</td>
                <td>Get Products</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
            <tr>
                <td><?= $base_url ?>/get-inventory</td>
                <td>Get Products</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
        </tbody>
    </table>
</div>