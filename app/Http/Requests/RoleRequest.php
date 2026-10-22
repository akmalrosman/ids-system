<?php

namespace App\Http\Requests;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permission::all())],
        ];
    }

    public function roleData(): array
    {
        $data = [
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'permissions' => array_values($this->validated('permissions', [])),
        ];

        // The slug is set once on create; the super-admin slug must never change.
        if (! $this->route('role')) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
