<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\QrCode;
use App\Core\Auth;

class QrCodeController extends Controller {
    public function index(Request $request): void {
        $this->middlewareAuth();

        $this->view('qr/index', [
            'qrRows' => QrCode::dashboardRows(),
            'baseMenuUrl' => $this->baseMenuUrl(),
        ]);
    }

    public function generate(Request $request): void {
        $this->middlewareAuth();

        $tableRoomId = (string)$request->input('table_room_id', '');
        if ($tableRoomId === '') {
            Session::flash('error', 'Choose a table or room before generating a QR code.');
            $this->redirect('/qr-codes');
        }

        QrCode::generateForTableRoom($tableRoomId);
        ActivityLog::log(Auth::businessId(), Auth::userId(), 'generate_qr_code', 'Generated a table or room QR code.');

        Session::flash('success', 'QR code generated successfully.');
        $this->redirect('/qr-codes');
    }

    public function generateMissing(Request $request): void {
        $this->middlewareAuth();

        $created = QrCode::generateForAllMissing();
        ActivityLog::log(Auth::businessId(), Auth::userId(), 'generate_missing_qr_codes', "Generated {$created} missing QR codes.");

        Session::flash('success', "{$created} missing QR code(s) generated.");
        $this->redirect('/qr-codes');
    }

    public function revoke(Request $request): void {
        $this->middlewareAuth();

        $qrId = (string)$request->input('qr_id', '');
        if ($qrId === '') {
            Session::flash('error', 'Choose an active QR code before revoking.');
            $this->redirect('/qr-codes');
        }

        $revoked = QrCode::revoke($qrId);
        if ($revoked) {
            ActivityLog::log(Auth::businessId(), Auth::userId(), 'revoke_qr_code', 'Revoked an active QR code.');
            Session::flash('success', 'QR code revoked.');
        } else {
            Session::flash('error', 'QR code was already inactive or not found.');
        }

        $this->redirect('/qr-codes');
    }

    private function baseMenuUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
        return "{$scheme}://{$host}/menu?token=";
    }
}
