<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // UUID público exposto ao frontend (nunca expor o ID interno)
            $table->uuid('uuid')->unique()->index();

            // Origem da conversa: web, api, whatsapp, etc.
            // EXTENSÃO FUTURA: vincular a um canal/tenant via foreign key
            $table->string('source', 50)->default('web');

            // Status do ciclo de vida da conversa
            $table->enum('status', ['active', 'closed', 'transferred'])->default('active')->index();

            // IP do cliente para rastreabilidade e rate limiting futuro
            $table->string('ip_address', 45)->nullable();

            // User-agent para analytics
            $table->string('user_agent')->nullable();

            // Metadados flexíveis: nome do visitante, email, página de origem, etc.
            // EXTENSÃO FUTURA: armazenar identificação do usuário autenticado
            $table->json('metadata')->nullable();

            // Modelo e system prompt usados nesta conversa (snapshot para auditoria)
            // Permite que mudanças futuras no prompt não alterem histórico
            $table->string('model')->nullable();
            $table->text('system_prompt')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
