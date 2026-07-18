<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Business;
use PDOException;

class ApiController extends Controller {
    private const BUSINESS_TYPES = ['restaurant', 'hotel', 'villa', 'cafe', 'bar', 'resort', 'cloud_kitchen'];

    public function options(Request $request): void {
        $this->apiHeaders();
        http_response_code(204);
        exit;
    }

    public function login(Request $request): void {
        $this->apiHeaders();

        $data = $request->all();
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$isValid) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->getErrors(),
            ], 422);
        }

        $business = Business::findLoginByEmail((string)$data['email']);
        if (!$business || !password_verify((string)$data['password'], $business['password_hash'])) {
            $this->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        Session::regenerate();
        Session::set('business_id', $business['id']);
        Session::set('user_id', $business['user_id']);
        Session::set('business_name', $business['name']);
        Session::set('owner_email', $business['email']);

        ActivityLog::log($business['id'], $business['user_id'], 'api_login', 'Admin logged in through API.');

        $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'business_id' => $business['id'],
                'admin_user_id' => $business['user_id'],
                'business_name' => $business['name'],
                'email' => $business['email'],
            ],
        ]);
    }

    public function register(Request $request): void {
        $this->apiHeaders();

        $data = $request->all();
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'business_name' => 'required|min:3',
            'owner_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone_number' => 'required|min:8',
        ]);

        if (!empty($data['business_type']) && !in_array($data['business_type'], self::BUSINESS_TYPES, true)) {
            $errors = $validator->getErrors();
            $errors['business_type'][] = 'The business type is invalid.';
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        if (!$isValid) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->getErrors(),
            ], 422);
        }

        try {
            $registered = Business::register($data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'A business or admin user with this email already exists.',
                    'errors' => [
                        'email' => ['The email has already been registered.'],
                    ],
                ], 409);
            }

            throw $e;
        }

        $this->json([
            'success' => true,
            'message' => 'Business registered successfully.',
            'data' => $registered,
        ], 201);
    }

    private function apiHeaders(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
    }
}
