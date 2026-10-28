<?php

namespace Modules\Auth\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Modules\Auth\App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * جستجوی کاربر ابتدا در دیتابیس لوکال، سپس در گستراب
     *
     */

    public function login(array $credentials)
    {
        $user = User::where('mobile', $credentials['mobile'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'mobile' => ['شماره موبایل یا کلمه عبور اشتباه است.'],
            ]);
        }

        if (!$user->canLogin()) {
            throw ValidationException::withMessages([
                'mobile' => ['این حساب اجازه ورود به سامانه را ندارد.'],
            ]);
        }

        $token = $user->createToken('erp_omrani_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * تولید و ارسال OTP
     */
    public function sendOtp(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'mobile' => ['کاربری با این شماره همراه یافت نشد.']
            ]);
        }

        // تولید کد 5 رقمی
        $otp = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        // ارسال پیامک از طریق سرویس Msgway
        $smsSent = $this->sendSmsViaMsgway($mobile, $otp);

        if (!$smsSent) {
            $user->forceFill(['otp_code' => null, 'otp_expires_at' => null])->save();
            throw new \RuntimeException('ارسال کد تایید انجام نشد.');
        }

        return [
            'message' => 'کد تایید ارسال شد.',
            'expires_in' => 300 // 5 دقیقه به ثانیه
        ];
    }

    /**
     * بررسی صحت OTP
     */
    public function verifyOtp(string $mobile, string $otp): User
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user || $user->otp_code !== $otp || Carbon::now()->isAfter($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['کد تایید نامعتبر یا منقضی شده است.']
            ]);
        }

        return $user;
    }

    /**
     * تغییر رمز عبور
     */
    public function resetPassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();
    }

    /**
     * متد خصوصی ارسال پیامک از طریق Msgway
     */
    /**
     * متد خصوصی ارسال پیامک از طریق Msgway
     */
    private function sendSmsViaMsgway(string $mobile, string $code): bool
    {
        $apiKey = (string) config('services.msgway.api_key');
        if ($apiKey === '') {
            Log::error('Msgway API key is not configured.');
            return false;
        }
        $params = [
            "mobile" => $mobile,
            "method" => "sms",
            "provider" => (int) config('services.msgway.provider', 0),
            "templateID" => (int) config('services.msgway.template_id_default', 0),
            "params" => [
                $code,
                "راه پیام",
                "msgway.com"

            ]
        ];
        try {
            $response = Http::timeout(15)
                ->withHeaders(['apiKey' => $apiKey])
                ->post('https://api.msgway.com/send', $params);
        } catch (\Throwable $e) {
            Log::error("SMS request failed for {$mobile}: {$e->getMessage()}");
            return false;
        }

        Log::info("SMS Response for {$mobile} (HTTP {$response->status()}): " . $response->body());

        return $response->successful();
    }




    public function findOrCreateUser(string $mobile): array
    {
        try {
            // جستجو در دیتابیس لوکال
            $user = User::where('mobile', $mobile)->first();

            if ($user) {
                return [
                    'found' => true,
                    'source' => 'local',
                    'user' => $user,
                    'message' => 'کاربر در سیستم یافت شد.'
                ];
            }

            // جستجو در گستراب
            $gtarabarEmployee = $this->searchInGtarabar($mobile);

            if (!$gtarabarEmployee) {
                return [
                    'found' => false,
                    'source' => null,
                    'user' => null,
                    'message' => 'کاربری با این شماره موبایل در سیستم یافت نشد.'
                ];
            }

            // بررسی کاربر با این personnel_code
            $existingUser = User::where('personnel_code', $gtarabarEmployee->PersonnelCode)->first();
            $email = trim((string) ($gtarabarEmployee->Email ?? ''));
            $email = $email === '' ? null : $email;
            if ($existingUser) {
                $existingUser->update([
                    'mobile' => $mobile,
                    'name' => $gtarabarEmployee->FullName,
                    'email' => $email ?: $existingUser->email,
                    'national_code' => $gtarabarEmployee->NationalID ?? $existingUser->national_code,
                ]);

                // اطمینان از داشتن Role کارمند
                $this->ensureEmployeeRole($existingUser);

                return [
                    'found' => true,
                    'source' => 'gtarabar_updated',
                    'user' => $existingUser,
                    'message' => 'اطلاعات کاربر به‌روزرسانی شد.'
                ];
            }

            // ایجاد کاربر جدید در دیتابیس لوکال
            $newUser = $this->createUserFromGtarabar($gtarabarEmployee);

            // ✅ اختصاص خودکار Role کارمند
            $this->assignDefaultRole($newUser);

            return [
                'found' => true,
                'source' => 'gtarabar',
                'user' => $newUser,
                'message' => 'کاربر از سیستم گستراب وارد شد.'
            ];

        } catch (\Exception $e) {
            Log::error('Error in findOrCreateUser: ' . $e->getMessage());
            return [
                'found' => false,
                'source' => null,
                'user' => null,
                'message' => 'خطایی در پردازش درخواست رخ داد.'
            ];
        }
    }

    /**
     * اختصاص Role پیش‌فرض "employee" به کاربر جدید
     */
    private function assignDefaultRole(User $user): void
    {
        $employeeRole = Role::where('name', 'پرسنل')->first();

        if ($employeeRole && !$user->hasRole('پرسنل')) {
            $user->assignRole($employeeRole);
            Log::info("Role 'employee' assigned to user: {$user->mobile}");
        }
    }

    /**
     * اطمینان از اینکه کاربر Role کارمند را دارد
     */
    private function ensureEmployeeRole(User $user): void
    {
        if (!$user->hasRole('پرسنل')) {
            $this->assignDefaultRole($user);
        }
    }

    /**
     * جستجو در دیتابیس گستراب بر اساس شماره موبایل
     */
    private function searchInGtarabar(string $mobile): ?object
    {
        try {
            return DB::connection('gtarabar')
                ->table('HCM3.Employee AS e')
                ->join('GNR3.Party AS p', 'p.PartyID', '=', 'e.PartyRef')
                ->where('p.Mobile', $mobile)
                ->select(
                    'e.EmployeeID',
                    'e.Code AS PersonnelCode',
                    'e.EmploymentNumber',
                    'p.FullName',
                    'p.NationalID',
                    'p.Mobile',
                    'p.Email'
                )
                ->first();
        } catch (\Exception $e) {
            Log::error('Error searching in Gtarabar: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ایجاد کاربر در دیتابیس لوکال از اطلاعات گستراب
     */
    private function createUserFromGtarabar(object $employee): User
    {
        $email = trim((string) ($employee->Email ?? ''));

        if ($email === '') {
            $email = null;
        }
        return User::create([
            'name' => $employee->FullName,
            'mobile' => $employee->Mobile,
            'email' => $email,
            'national_code' => $employee->NationalID ?? null,
            'personnel_code' => $employee->PersonnelCode,
            'password' => Hash::make(Str::random(16)),
        ]);
    }

    /**
     * لاگین کاربر

    public function login(User $user, string $password): array
    {
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['کلمه عبور اشتباه است.']
            ]);
        }

        $token = $user->createToken('erp_omrani_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    } */
}
