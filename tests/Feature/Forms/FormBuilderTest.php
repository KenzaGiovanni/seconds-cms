<?php

use App\Enums\Role;
use App\Livewire\Forms\FormBuilder;
use App\Livewire\Forms\FormList;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::Admin->value);
});

// -- Access --

it('blocks guests from the forms admin', function () {
    $this->get('/admin/forms')->assertRedirect('/admin/login');
});

it('allows content managers to access forms', function () {
    actingAs($this->admin)->get('/admin/forms')->assertOk();
});

// -- Build --

it('creates a form with a field schema', function () {
    Livewire::actingAs($this->admin)->test(FormBuilder::class)
        ->set('name', 'Contact')
        ->set('slug', 'contact')
        ->set('recipientEmail', 'hello@example.com')
        ->call('addField')
        ->set('fields.0.label', 'Your name')
        ->set('fields.0.type', 'text')
        ->set('fields.0.required', true)
        ->call('addField')
        ->set('fields.1.label', 'Topic')
        ->set('fields.1.type', 'select')
        ->set('fields.1.options', 'Sales, Support')
        ->call('save');

    $form = Form::where('slug', 'contact')->first();

    expect($form)->not->toBeNull()
        ->and($form->recipient_email)->toBe('hello@example.com')
        ->and($form->fields)->toHaveCount(2)
        ->and($form->fields[0])->toMatchArray(['key' => 'your_name', 'type' => 'text', 'label' => 'Your name', 'required' => true])
        ->and($form->fields[1]['type'])->toBe('select')
        ->and($form->fields[1]['options'])->toBe(['sales' => 'Sales', 'support' => 'Support']);
});

it('auto-generates the slug from the name', function () {
    $component = Livewire::actingAs($this->admin)->test(FormBuilder::class)
        ->set('name', 'Get In Touch');

    expect($component->get('slug'))->toBe('get-in-touch');
});

it('drops blank field rows on save', function () {
    Livewire::actingAs($this->admin)->test(FormBuilder::class)
        ->set('name', 'Sparse')
        ->set('slug', 'sparse')
        ->call('addField')
        ->call('addField')
        ->set('fields.0.label', 'Email')
        ->set('fields.0.type', 'email')
        ->call('save');

    expect(Form::where('slug', 'sparse')->first()->fields)->toHaveCount(1);
});

it('exposes field schemas as FieldSchema objects', function () {
    $form = Form::create([
        'name' => 'Schema',
        'slug' => 'schema',
        'fields' => [
            ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
        ],
    ]);

    $schemas = $form->fieldSchemas();

    expect($schemas)->toHaveCount(1)
        ->and($schemas[0]->key)->toBe('name')
        ->and($schemas[0]->required)->toBeTrue();
});

// -- Edit + delete --

it('loads an existing form back into the builder', function () {
    $form = Form::create([
        'name' => 'Editable',
        'slug' => 'editable',
        'fields' => [['key' => 'topic', 'type' => 'select', 'label' => 'Topic', 'options' => ['a' => 'A', 'b' => 'B']]],
        'recipient_email' => 'x@example.com',
    ]);

    $component = Livewire::actingAs($this->admin)->test(FormBuilder::class, ['id' => $form->id]);

    expect($component->get('name'))->toBe('Editable')
        ->and($component->get('fields')[0]['options'])->toBe('A, B');
});

it('deletes a form', function () {
    $form = Form::create(['name' => 'Trash', 'slug' => 'trash', 'fields' => []]);

    Livewire::actingAs($this->admin)->test(FormList::class)->call('delete', $form->id);

    expect(Form::find($form->id))->toBeNull();
});

// -- Submissions --

it('lists submissions for a form', function () {
    $form = Form::create(['name' => 'Polled', 'slug' => 'polled', 'fields' => []]);
    FormSubmission::create(['form_id' => $form->id, 'data' => ['name' => 'Ada'], 'submitted_at' => now()]);
    FormSubmission::create(['form_id' => $form->id, 'data' => ['name' => 'Linus'], 'submitted_at' => now()]);

    actingAs($this->admin)->get(route('admin.forms.submissions', $form->id))
        ->assertOk()
        ->assertSee('Ada')
        ->assertSee('Linus');
});
