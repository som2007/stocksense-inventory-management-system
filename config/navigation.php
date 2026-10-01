<?php
declare(strict_types=1);

/**
 * Sidebar definition. 'built' => false items stay hidden until their module is delivered.
 * roles: who can see the item. 'module' is only used by the dashboard status card.
 */
return [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'href' => 'dashboard', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M0-M1', 'built' => true],
    ['key' => 'products', 'label' => 'Products', 'icon' => 'bi-box-seam', 'href' => 'products', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M3', 'built' => false],
    ['group' => 'Operations'],
    ['key' => 'receipts', 'label' => 'Receipts', 'icon' => 'bi-box-arrow-in-down', 'href' => 'receipts', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M4', 'built' => false],
    ['key' => 'deliveries', 'label' => 'Delivery Orders', 'icon' => 'bi-truck', 'href' => 'deliveries', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M5', 'built' => false],
    ['key' => 'transfers', 'label' => 'Internal Transfers', 'icon' => 'bi-arrow-left-right', 'href' => 'transfers', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M6', 'built' => false],
    ['key' => 'adjustments', 'label' => 'Inventory Adjustment', 'icon' => 'bi-sliders', 'href' => 'adjustments', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M7', 'built' => false],
    ['key' => 'ledger', 'label' => 'Move History', 'icon' => 'bi-clock-history', 'href' => 'move-history', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M8', 'built' => false],
    ['key' => 'approvals', 'label' => 'Approvals', 'icon' => 'bi-patch-check', 'href' => 'approvals', 'roles' => ['inventory_manager'], 'module' => 'M4', 'built' => false],
    ['group' => 'Settings'],
    ['key' => 'warehouses', 'label' => 'Warehouse', 'icon' => 'bi-building', 'href' => 'warehouses', 'roles' => ['inventory_manager', 'warehouse_staff'], 'module' => 'M2', 'built' => false],
    ['key' => 'staff', 'label' => 'Staff', 'icon' => 'bi-people', 'href' => 'staff', 'roles' => ['inventory_manager'], 'module' => 'M10', 'built' => false],
];
