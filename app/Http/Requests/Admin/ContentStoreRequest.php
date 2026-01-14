<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ContentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->user_type, ['admin', 'staff']);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug if not provided
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge([
                'slug' => Str::slug($this->title)
            ]);
        }

        // Trim whitespace
        if ($this->has('title')) {
            $this->merge([
                'title' => trim($this->title)
            ]);
        }

        if ($this->has('body')) {
            $this->merge([
                'body' => trim($this->body)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|min:5|max:255',
            'slug' => 'nullable|string|max:255|unique:contents,slug',
            'body' => 'required|string|min:50',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft,published,scheduled',
            'featured_image' => 'nullable|image|max:2048', // 2MB max
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:255',
            'publish_at' => 'nullable|date|after:now',
        ];

        // If updating, ignore current record for slug uniqueness
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $contentId = $this->route('id');
            $rules['slug'] = 'nullable|string|max:255|unique:contents,slug,' . $contentId;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul konten wajib diisi',
            'title.min' => 'Judul konten minimal 5 karakter',
            'title.max' => 'Judul konten maksimal 255 karakter',
            'slug.unique' => 'Slug sudah digunakan, silakan gunakan slug lain',
            'body.required' => 'Isi konten wajib diisi',
            'body.min' => 'Isi konten minimal 50 karakter',
            'featured_image.image' => 'File harus berupa gambar',
            'featured_image.max' => 'Ukuran gambar maksimal 2MB',
            'seo_title.max' => 'SEO Title maksimal 60 karakter',
            'seo_description.max' => 'SEO Description maksimal 160 karakter',
            'publish_at.after' => 'Tanggal publikasi harus di masa depan',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Sanitize HTML content
        if ($this->has('body')) {
            $this->merge([
                'body' => $this->sanitizeHtml($this->body)
            ]);
        }
    }

    /**
     * Sanitize HTML content
     */
    protected function sanitizeHtml(string $html): string
    {
        // Allow only safe HTML tags
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td>';

        return strip_tags($html, $allowedTags);
    }
}
