<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Syndicate\Inspector\Enums\RemarkLevel;

return new class extends Migration {
    public function up(): void
    {
        $severityExpr = $this->levelSeverityCaseExpr();

        Schema::create('inspection_reports', function (Blueprint $table) use ($severityExpr) {
            $table->id();

            $table->nullableMorphs('inspectable', 'inspectable_report_index');
            $table->string('url')->index();

            $table->string('level')->index();
            $table->json('finding_counts')->nullable();
            $table->json('check_counts')->nullable();

            $table->unsignedSmallInteger('level_severity')->storedAs($severityExpr);
            $table->index('level_severity', 'inspection_report_severity_idx');

            $table->timestamps();
        });

        Schema::create('inspection_remarks', function (Blueprint $table) use ($severityExpr) {
            $table->id();

            $table->foreignId('inspection_report_id')
                ->constrained('inspection_reports')
                ->cascadeOnDelete();

            $table->nullableMorphs('inspectable', 'inspectable_remark_index');
            $table->string('url')->index();

            $table->string('level')->index();
            $table->string('check')->index();
            $table->string('checklist')->index();

            $table->text('message');
            $table->json('details')->nullable();
            $table->json('config')->nullable();

            $table->unsignedSmallInteger('level_severity')->storedAs($severityExpr);
            $table->index('level_severity', 'inspection_remark_severity_idx');

            $table->timestamps();
        });
    }

    private function levelSeverityCaseExpr(): string
    {
        $parts = array_map(
            function (RemarkLevel $l) {
                $val = str_replace("'", "''", $l->value);
                $sev = $l->getSeverity();
                return "WHEN '{$val}' THEN {$sev}";
            },
            RemarkLevel::cases()
        );

        return 'CASE level ' . implode(' ', $parts) . ' ELSE 0 END';
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_remarks');
        Schema::dropIfExists('inspection_reports');
    }
};
