<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AccentColorForm extends Component
{
    public string $accentColor = '';

    /** Готовые варианты в палитре YouTrack */
    public const PRESETS = [
        '#FF318C' => 'Фуксия',
        '#366ACF' => 'Синий',
        '#59A869' => 'Зелёный',
        '#F6743E' => 'Оранжевый',
        '#9C6BDE' => 'Фиолетовый',
        '#F5C538' => 'Жёлтый',
    ];

    public function mount(): void
    {
        $this->accentColor = Auth::user()->accentColor();
    }

    public function save(): void
    {
        $this->validate(
            ['accentColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']],
            attributes: ['accentColor' => 'цвет'],
        );

        Auth::user()->update(['accent_color' => mb_strtoupper($this->accentColor)]);

        // Полная перезагрузка, чтобы CSS-переменные в layout применились везде
        $this->redirectRoute('profile');
    }

    public function render()
    {
        return view('livewire.profile.accent-color-form', [
            'presets' => self::PRESETS,
            'default' => User::DEFAULT_ACCENT,
        ]);
    }
}
