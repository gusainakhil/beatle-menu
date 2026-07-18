<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;

abstract class ReportService {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    protected function getBusinessId(): string {
        return Auth::businessId();
    }
}
