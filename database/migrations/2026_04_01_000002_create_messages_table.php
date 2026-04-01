<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            // Role conforme especificação OpenAI: user, assistant, system
            $table->enum('role', ['user', 'assistant', 'system'])->index();

            // Conteúdo textual da mensagem
            $table->text('content');

            // Modelo que gerou a resposta (null para mensagens do usuário)
            $table->string('model')->nullable();

            // Contagem de tokens para controle de custo
            // EXTENSÃO FUTURA: agregar tokens por conversa/dia para billing
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            // Resposta bruta da OpenAI para debugging e auditoria
            // Nullable: mensagens do usuário não têm raw_response
            $table->json('raw_response')->nullable();

            // EXTENSÃO FUTURA: campos para function calling e file search
            // $table->json('tool_calls')->nullable();
            // $table->json('tool_results')->nullable();
            // $table->string('file_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
