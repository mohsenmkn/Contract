<?php

namespace Modules\Signature\App\Http\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\App\Models\User;
use Modules\Signature\App\Models\SignatureAsset;
use Modules\Signature\App\Services\SignatureImageService;

class SignatureAssetController extends Controller
{
    public function __construct(private SignatureImageService $images)
    {
    }

    public function status(): JsonResponse
    {
        try {
            $this->images->activeFor(auth()->user());
            $hasSignature = true;
        } catch (DomainException) {
            $hasSignature = false;
        }

        return response()->json(['success' => true, 'data' => ['has_signature_image' => $hasSignature]]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:512',
        ]);

        try {
            $asset = $this->images->store(auth()->user(), $validated['image']);
        } catch (DomainException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تصویر امضای شما با موفقیت ذخیره شد.',
            'data' => ['id' => $asset->id, 'kind' => $asset->kind],
        ], 201);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorizeAdministrator();

        $search = trim((string) $request->query('search', ''));
        $users = User::query()
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super-admin'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($where) use ($search) {
                    $where->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(min(max((int) $request->query('per_page', 25), 1), 100));

        $ownerType = (new User())->getMorphClass();
        $activeOwnerIds = SignatureAsset::query()
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $users->getCollection()->pluck('id'))
            ->where('kind', 'signature')
            ->where('is_active', true)
            ->pluck('owner_id')
            ->all();

        return response()->json([
            'success' => true,
            'data' => $users->getCollection()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'has_signature_image' => in_array($user->id, $activeOwnerIds),
            ])->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function storeForUser(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdministrator();
        abort_if($user->hasRole('super-admin'), 422, 'برای مدیر کل سامانه نیازی به ثبت امضای کاربری نیست.');

        $validated = $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:512',
        ]);

        try {
            $asset = $this->images->store($user, $validated['image']);
        } catch (DomainException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تصویر امضای کاربر ثبت شد.',
            'data' => ['id' => $asset->id, 'user_id' => $user->id, 'kind' => $asset->kind],
        ], 201);
    }

    private function authorizeAdministrator(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403, 'فقط مدیر کل سامانه مجاز به مدیریت امضای کاربران است.');
    }
}
