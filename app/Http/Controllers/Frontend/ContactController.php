<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Classic HTML contact form — no JS framework dependency.
 * Validates input, sends a mail if a destination is configured,
 * and always returns a user-friendly response via session flash.
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly FrontendResolverService $resolver,
    ) {}

    /** Display the contact form. */
    public function show(): View
    {
        $siteName = $this->resolver->siteName();
        $seo = $this->resolver->seo(null, null, [
            'title'   => 'Contact – ' . $siteName,
            'og_type' => 'website',
        ]);

        return view('frontend.contact.index', [
            'siteName'    => $siteName,
            'seo'         => $seo,
            'primaryMenu' => $this->resolver->menu('primary'),
        ]);
    }

    /** Handle the form POST. */
    public function send(Request $request): RedirectResponse
    {
        $maxChars = (int) config('catmin_frontend.contact_max_chars', 2000);

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email:filter', 'max:254'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:' . $maxChars],
        ]);

        $toEmail = config('catmin_frontend.contact_to_email');

        if ($toEmail) {
            try {
                $body = implode("\n\n", [
                    'Nom : ' . $validated['name'],
                    'Email : ' . $validated['email'],
                    'Sujet : ' . ($validated['subject'] ?? '(non renseigné)'),
                    '---',
                    $validated['message'],
                ]);

                \Illuminate\Support\Facades\Mail::raw($body, function ($msg) use ($validated, $toEmail): void {
                    $subject = ($validated['subject'] ?? '') !== ''
                        ? $validated['subject']
                        : 'Message depuis le formulaire de contact';

                    $msg->to($toEmail)
                        ->replyTo($validated['email'], $validated['name'])
                        ->subject('[Contact] ' . $subject);
                });
            } catch (\Throwable) {
                // Mail failure must not expose internal info to visitor
            }
        }

        return redirect()->route('frontend.contact')
            ->with('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');
    }
}
