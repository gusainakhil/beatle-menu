<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Business;
use App\Models\ActivityLog;

class SettingsController extends Controller {
    public function index(Request $request): void {
        $this->middlewareAuth();

        $businessId = Auth::businessId();
        $business = Business::find($businessId);
        $settings = Business::getSettings($businessId);
        $logs = ActivityLog::getRecent(10);

        $this->view('settings/index', [
            'business' => $business,
            'settings' => $settings,
            'logs' => $logs
        ]);
    }

    public function update(Request $request): void {
        $this->middlewareAuth();

        $businessId = Auth::businessId();
        $data = $request->all();

        $validator = new Validator();
        $validator->validate($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ]);

        if (!empty($validator->getErrors())) {
            Session::flash('errors', $validator->getErrors());
            $this->redirect('/settings');
        }

        Business::updateInfo($businessId, $data['name'], $data['email'], $data['phone'] ?? null, $data['address'] ?? null);
        ActivityLog::log($businessId, Auth::userId(), 'update_settings', 'Updated business configurations.');

        Session::flash('success', 'Settings updated successfully!');
        $this->redirect('/settings');
    }
}
