<?php

namespace App\Livewire\Forms;

use App\Enums\Permission;
use App\Models\Form;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Submissions')]
class FormSubmissions extends Component
{
    use WithPagination;

    public Form $form;

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $this->form = Form::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.forms.form-submissions', [
            'submissions' => $this->form->submissions()
                ->latest('submitted_at')
                ->paginate(20),
        ]);
    }
}
