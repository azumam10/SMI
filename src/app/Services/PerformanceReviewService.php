<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceReviewService
{
    // ─── Mapping kategori dari PerformanceReview ke Employee ──
    private function mapCategoryToEmployee(string $category): string
    {
        return match ($category) {
            'Outstanding' => 'High',
            'Excellent'   => 'High',
            'Good'        => 'Med',
            'Fair'        => 'Low',
            'Poor'        => 'Low',
            default       => 'Med',
        };
    }

    public function create(array $data): PerformanceReview
    {
        return DB::transaction(function () use ($data) {
            // 1. Cek duplikat
            $exists = PerformanceReview::query()
                ->where('employee_id', $data['employee_id'])
                ->where('year', $data['year'])
                ->where('semester', $data['semester'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Karyawan sudah dinilai pada periode ini.',
                ]);
            }

            // 2. Tentukan status
            $user = auth()->user();
            $employee = Employee::where('user_id', $user->id)->first();
            $isSupervisor = Employee::where('supervisor_id', $employee?->id)->exists();
            $isHrdOrAdmin = $user->hasAnyRole(['hrd', 'super_admin']);

            $status = PerformanceReview::STATUS_SUBMITTED;
            $approvedBy = null;
            $approvedAt = null;

            if ($isHrdOrAdmin) {
                $status = PerformanceReview::STATUS_APPROVED;
                $approvedBy = $user->id;
                $approvedAt = now();
            } elseif ($isSupervisor) {
                $status = PerformanceReview::STATUS_SUBMITTED;
            } else {
                throw ValidationException::withMessages([
                    'employee_id' => 'Anda tidak memiliki akses memberi penilaian.',
                ]);
            }

            // 3. Hitung final_score & category
            $finalScore = PerformanceReview::calculateFinalScore($data);
            $category = PerformanceReview::resolveCategory($finalScore);

            // 4. Pastikan category di $data di-overwrite
            $data['final_score'] = $finalScore;
            $data['category'] = $category;
            $data['status'] = $status;
            $data['approved_by'] = $approvedBy;
            $data['approved_at'] = $approvedAt;

            // 5. Simpan
            $review = PerformanceReview::create($data);

            // 6. Update employee jika approved
            if ($status === PerformanceReview::STATUS_APPROVED) {
                Employee::whereKey($data['employee_id'])->update([
                    'performance_score'    => $finalScore,
                    'performance_category' => $this->mapCategoryToEmployee($category),
                ]);
            }

            return $review;
        });
    }

    public function approve(PerformanceReview $review): void
    {
        if ($review->status !== PerformanceReview::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya penilaian dengan status "Menunggu Approval" yang bisa disetujui.',
            ]);
        }

        $review->update([
            'status'      => PerformanceReview::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update employee dengan mapping
        Employee::whereKey($review->employee_id)->update([
            'performance_score'    => $review->final_score,
            'performance_category' => $this->mapCategoryToEmployee($review->category),
        ]);
    }

    public function revise(PerformanceReview $review): void
    {
        $review->update([
            'status'      => PerformanceReview::STATUS_REVISED,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function canEdit(User $user, PerformanceReview $review): bool
    {
        if ($user->hasAnyRole(['super_admin', 'hrd'])) {
            return true;
        }

        if ($user->hasRole('kepala_bagian')) {
            $employee = Employee::where('user_id', $user->id)->first();
            $isSubordinate = Employee::where('supervisor_id', $employee?->id)
                ->where('id', $review->employee_id)
                ->exists();

            return $isSubordinate && $review->reviewer_id === $user->id
                && in_array($review->status, [PerformanceReview::STATUS_DRAFT, PerformanceReview::STATUS_SUBMITTED]);
        }

        return false;
    }

    public function canDelete(User $user, PerformanceReview $review): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('kepala_bagian')) {
            $employee = Employee::where('user_id', $user->id)->first();
            $isSubordinate = Employee::where('supervisor_id', $employee?->id)
                ->where('id', $review->employee_id)
                ->exists();

            return $isSubordinate && $review->reviewer_id === $user->id
                && $review->status !== PerformanceReview::STATUS_APPROVED;
        }

        return false;
    }
}