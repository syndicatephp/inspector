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

            $table->morphs('inspectable', 'inspectable_index');
            $table->unique(['inspectable_id', 'inspectable_type'], 'inspectable_unique');

            $table->string('level')->default(RemarkLevel::SUCCESS->value)->index();
            $table->json('finding_counts')->nullable();
            $table->json('check_counts')->nullable();

            $table->unsignedSmallInteger('level_severity')->storedAs($severityExpr);
            $table->index('level_severity', 'sir_level_severity_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reports');
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

        return 'CASE level '.implode(' ', $parts).' ELSE 0 END';
    }
};
