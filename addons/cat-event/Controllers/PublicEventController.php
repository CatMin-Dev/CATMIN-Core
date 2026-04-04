<?php

declare(strict_types=1);

namespace Addons\CatEvent\Controllers;

use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Services\EventPublicFlowService;
use Addons\CatEvent\Services\EventRegistrationService;
use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicEventController extends Controller
{
    public function __construct(
        private readonly EventPublicFlowService $publicFlowService,
        private readonly EventRegistrationService $registrationService,
        private readonly FrontendResolverService $frontendResolver,
    ) {
    }

    public function show(string $slug): View
    {
        $event = $this->publicFlowService->publicBySlug($slug);
        abort_if($event === null, 404);

        $state = $this->publicFlowService->buildPublicState($event);

        $formToken = bin2hex(random_bytes(16));
        session()->put('event_public_form_token_' . $event->id, $formToken);

        $siteName = $this->frontendResolver->siteName();
        $seo = $this->frontendResolver->seo(null, null, [
            'title' => $event->title . ' - ' . $siteName,
            'description' => (string) str($event->description ?? '')->stripTags()->limit(160),
            'og_type' => 'website',
        ]);

        return view()->file(base_path('addons/cat-event/Views/public/show.blade.php'), [
            'event' => $event,
            'state' => $state,
            'formToken' => $formToken,
            'siteName' => $siteName,
            'seo' => $seo,
            'primaryMenu' => $this->frontendResolver->menu('primary'),
        ]);
    }

    public function register(Request $request, string $slug): RedirectResponse
    {
        $event = $this->publicFlowService->publicBySlug($slug);
        abort_if($event === null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'seats_count' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . max(1, (int) ($event->max_places_per_registration ?? 1)),
            ],
            'form_token' => ['required', 'string', 'size:32'],
            'consent' => ['required', Rule::in(['1'])],
        ]);

        $expectedToken = (string) session()->get('event_public_form_token_' . $event->id, '');
        if ($expectedToken === '' || !hash_equals($expectedToken, (string) $validated['form_token'])) {
            return back()->withErrors([
                'global' => 'Le formulaire a expire. Merci de recharger la page.',
            ])->withInput();
        }

        try {
            $participant = $this->registrationService->register($event, $validated);
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'global' => $e->getMessage(),
            ])->withInput();
        }

        session()->forget('event_public_form_token_' . $event->id);

        $signedUrl = URL::signedRoute('frontend.events.confirmation', [
            'slug' => $event->slug,
            'participant' => $participant->id,
        ]);

        return redirect($signedUrl)
            ->with('success', 'Inscription enregistree avec succes.');
    }

    public function confirmation(Request $request, string $slug, EventParticipant $participant): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $event = $this->publicFlowService->publicBySlug($slug);
        abort_if($event === null, 404);

        abort_unless((int) $participant->event_id === (int) $event->id, 404);

        $siteName = $this->frontendResolver->siteName();
        $seo = $this->frontendResolver->seo(null, null, [
            'title' => 'Confirmation - ' . $event->title . ' - ' . $siteName,
            'og_type' => 'website',
        ]);

        return view()->file(base_path('addons/cat-event/Views/public/confirmation.blade.php'), [
            'event' => $event,
            'participant' => $participant,
            'siteName' => $siteName,
            'seo' => $seo,
            'primaryMenu' => $this->frontendResolver->menu('primary'),
        ]);
    }
}
