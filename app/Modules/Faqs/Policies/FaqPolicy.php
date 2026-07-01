<?php

namespace App\Modules\Faqs\Policies;

use App\Models\User;
use App\Modules\Faqs\Models\Faq;

class FaqPolicy
{
    public function viewAny(User $user): bool   { return $user->can('faqs.view'); }
    public function view(User $user, Faq $faq): bool   { return $user->can('faqs.view'); }
    public function create(User $user): bool                     { return $user->can('faqs.create'); }
    public function update(User $user, Faq $faq): bool { return $user->can('faqs.update'); }
    public function delete(User $user, Faq $faq): bool { return $user->can('faqs.delete'); }
}
