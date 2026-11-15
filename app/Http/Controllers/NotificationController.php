<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Centro de notificaciones internas: citas, cambios, errores
     * relevantes e invitaciones.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('notifications.index', [
            'notificaciones' => $this->visibles($user)->orderByDesc('id')->paginate(20),
            'sinLeer' => $this->visibles($user)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, AppNotification $appNotification)
    {
        abort_unless($this->esVisible($request->user(), $appNotification), 403);

        $appNotification->update(['read_at' => now()]);

        return $this->respuesta($request, 'Notificación leída');
    }

    public function markAllRead(Request $request)
    {
        $this->visibles($request->user())->whereNull('read_at')->update(['read_at' => now()]);

        return $this->respuesta($request, 'Notificaciones marcadas como leídas');
    }

    /**
     * @return Builder<AppNotification>
     */
    private function visibles(User $user): Builder
    {
        return AppNotification::query()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere(function ($colectivas) use ($user) {
                    $colectivas->whereNull('user_id')->where(function ($ambito) use ($user) {
                        $ambito->where('business_id', $user->business_id);

                        if ($user->isSuperAdmin()) {
                            $ambito->orWhereNull('business_id');
                        }
                    });
                });
        });
    }

    private function esVisible(User $user, AppNotification $notificacion): bool
    {
        return $this->visibles($user)->whereKey($notificacion->id)->exists();
    }
}
