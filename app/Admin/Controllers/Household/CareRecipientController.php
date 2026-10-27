<?php

namespace App\Admin\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Admin\Models\CareRecipient;
use App\Admin\Models\Household;
use App\Admin\Authorization\CrmRecordAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CareRecipientController extends Controller
{
    public function __construct(protected CrmRecordAccess $recordAccess) {}

    private function validateRecipient(Request $request): array
    {
        return $request->validate([
            'first_name'           => ['required', 'string', 'max:100'],
            'age_band'             => ['required', Rule::in(config('crm.age_bands'))],
            'relationship'         => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function store(Request $request, Household $household)
    {
        $this->recordAccess->assertHousehold($request->user(), $household);
        $validated = $this->validateRecipient($request);

        try {
            $validated['household_id'] = $household->id;
            $validated['created_by']   = auth()->id();
            $validated['recipient_type'] = config('crm.recipient_type_by_age_band.'.$validated['age_band']);
            abort_unless($validated['recipient_type'], 422, 'The selected age band is not configured.');
            $validated['is_sensitive'] = ($validated['recipient_type'] === 'Child');

            $recipient = CareRecipient::create($validated);
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient added');
            flash()->success('Care recipient added.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update(Request $request, Household $household, CareRecipient $recipient)
    {
        $this->recordAccess->assertHousehold($request->user(), $household);
        abort_unless((int) $recipient->household_id === (int) $household->id, 404);
        abort_if($recipient->is_sensitive && ! $this->recordAccess->canViewSensitiveRecipientData($request->user()), 403);

        $validated = $this->validateRecipient($request);

        try {
            $validated['recipient_type'] = config('crm.recipient_type_by_age_band.'.$validated['age_band']);
            abort_unless($validated['recipient_type'], 422, 'The selected age band is not configured.');
            $validated['is_sensitive'] = ($validated['recipient_type'] === 'Child');

            $recipient->update($validated);
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient updated');
            flash()->success('Care recipient updated.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function destroy(Household $household, CareRecipient $recipient)
    {
        $this->recordAccess->assertHousehold(request()->user(), $household);
        abort_unless((int) $recipient->household_id === (int) $household->id, 404);
        abort_if($recipient->is_sensitive && ! $this->recordAccess->canViewSensitiveRecipientData(request()->user()), 403);

        try {
            activity()->causedBy(auth()->user())->performedOn($recipient)->log('Care recipient deleted');
            $recipient->delete();
            flash()->success('Care recipient deleted.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete care recipient.');
        }

        return back();
    }
}
