<?php

namespace App\Enums;

enum PermissionEmnum: string
{
    case VIEW_DASHBOARD = 'view_dashboard';
    case MANAGE_TRANSACTIONS = 'manage_transactions';
    case MANAGE_ACCOUNTS = 'manage_accounts';
    case MANAGE_TAGS = 'manage_tags';
    case MANAGE_MEMBERS = 'manage_members';
    case VIEW_ALL_DASHBOARDS = 'view_all_dashboards';
}
