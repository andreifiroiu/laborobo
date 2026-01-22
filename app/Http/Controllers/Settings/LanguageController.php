<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    /**
     * Show the language settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('account/language', [
            'availableLocales' => config('app.available_locales'),
            'localeNames' => config('app.locale_names'),
        ]);
    }

    /**
     * Update the user's language preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', Rule::in(config('app.available_locales'))],
        ]);

        // Update user preference
        $request->user()->update([
            'language' => $validated['language'],
        ]);

        // Set cookie for immediate effect (365 days)
        Cookie::queue('language', $validated['language'], 60 * 24 * 365);

        return back()->with('status', __('messages.language.updated'));
    }
}
