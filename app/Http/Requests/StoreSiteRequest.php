<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Models\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:sites,subdomain',
            ],
            'plan_id' => ['required', 'exists:plans,id'],
            'theme_id' => ['nullable', 'exists:themes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Subdomain must contain only lowercase letters, numbers, and hyphens. It cannot start or end with a hyphen.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $plan = Plan::find($this->input('plan_id'));
            $themeId = $this->input('theme_id');

            if (! $plan || blank($themeId)) {
                return;
            }

            $theme = Theme::find($themeId);

            if (! $theme) {
                return;
            }

            if ($theme->min_plan_level > $plan->level) {
                $validator->errors()->add('theme_id', 'The selected theme is not available for this plan.');
            }

            if (! $theme->zip_exists) {
                $validator->errors()->add('theme_id', 'The selected theme package file does not exist on the server.');
            }
        });
    }
}