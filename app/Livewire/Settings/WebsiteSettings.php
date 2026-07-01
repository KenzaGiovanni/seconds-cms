<?php

namespace App\Livewire\Settings;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Website Settings')]
class WebsiteSettings extends Component
{
    public string $siteName = '';

    public string $siteTagline = '';

    public string $siteEmail = '';

    public string $timezone = 'UTC';

    public string $dateFormat = 'd M Y';

    // Left untyped so an emptied number/select input coerces via validation
    // instead of throwing a TypeError on the Livewire property.
    public $postsPerPage = 10;

    public string $showOnFront = 'posts';

    public $frontPageId = null;

    /** Common PHP date formats offered in the dropdown. */
    public const DATE_FORMATS = [
        'd M Y',
        'j F Y',
        'd/m/Y',
        'm/d/Y',
        'Y-m-d',
        'D, d M Y',
        'l, j F Y',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::SettingsManage->value), 403);

        $this->siteName = (string) Setting::get('site_name', '');
        $this->siteTagline = (string) Setting::get('site_tagline', '');
        $this->siteEmail = (string) Setting::get('site_email', '');
        $this->timezone = (string) (Setting::get('timezone') ?: 'UTC');
        $this->dateFormat = (string) (Setting::get('date_format') ?: 'd M Y');
        $this->postsPerPage = (int) (Setting::get('posts_per_page') ?: 10);
        $this->showOnFront = Setting::get('show_on_front', 'posts') === 'page' ? 'page' : 'posts';
        $frontId = Setting::get('front_page_id');
        $this->frontPageId = $frontId ? (int) $frontId : null;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::SettingsManage->value), 403);

        $data = $this->validate([
            'siteName' => 'required|string|max:255',
            'siteTagline' => 'nullable|string|max:255',
            'siteEmail' => 'nullable|email|max:255',
            'timezone' => 'required|timezone',
            'dateFormat' => 'required|string|max:50',
            'postsPerPage' => 'required|integer|min:1|max:100',
            'showOnFront' => 'required|in:posts,page',
            'frontPageId' => 'nullable|integer|exists:contents,id',
        ]);

        Setting::set('site_name', $data['siteName']);
        Setting::set('site_tagline', $data['siteTagline'] ?? '');
        Setting::set('site_email', $data['siteEmail'] ?? '');
        Setting::set('timezone', $data['timezone']);
        Setting::set('date_format', $data['dateFormat']);
        Setting::set('posts_per_page', (string) $data['postsPerPage']);
        Setting::set('show_on_front', $data['showOnFront']);
        Setting::set('front_page_id', $data['showOnFront'] === 'page' && $data['frontPageId']
            ? (string) $data['frontPageId']
            : '');

        session()->flash('success', 'Website settings saved.');
    }

    public function render()
    {
        // Offer the presets, plus the current value if it is a custom one.
        $formats = collect(self::DATE_FORMATS)
            ->push($this->dateFormat)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('livewire.settings.website-settings', [
            'timezones' => \DateTimeZone::listIdentifiers(),
            'pages' => Page::orderBy('title')->get(['id', 'title']),
            'dateFormats' => $formats,
        ]);
    }
}
