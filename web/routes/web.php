<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasourceController;
use App\Http\Controllers\QueryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - QueryBase Admin Panel
|--------------------------------------------------------------------------
|
| Rotas do painel administrativo do QueryBase.
|
| Estrutura:
| /                    - Redireciona para dashboard
| /dashboard           - Página inicial com métricas
| /queries/*           - CRUD de queries SQL
| /datasources/*       - CRUD de fontes de dados
|
| NOTA: Por enquanto sem autenticação para simplificar o MVP.
| Em produção, adicionar middleware 'auth' em todas as rotas.
|
*/

// Redireciona home para dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Queries Routes
|--------------------------------------------------------------------------
|
| Resource routes geram automaticamente:
| GET    /queries           -> index   (listar)
| GET    /queries/create    -> create  (formulário de criação)
| POST   /queries           -> store   (salvar novo)
| GET    /queries/{query}   -> show    (exibir detalhes)
| GET    /queries/{query}/edit -> edit (formulário de edição)
| PUT    /queries/{query}   -> update  (atualizar)
| DELETE /queries/{query}   -> destroy (deletar)
|
| Rotas extras:
| POST   /queries/{query}/duplicate -> duplicate (clonar query)
| POST   /queries/{query}/toggle    -> toggle    (ativar/desativar)
|
*/
Route::resource('queries', QueryController::class);
Route::post('queries/{query}/duplicate', [QueryController::class, 'duplicate'])->name('queries.duplicate');
Route::post('queries/{query}/toggle', [QueryController::class, 'toggle'])->name('queries.toggle');

/*
|--------------------------------------------------------------------------
| Datasources Routes
|--------------------------------------------------------------------------
|
| Mesma estrutura de resource routes.
|
| Rotas extras:
| POST   /datasources/{datasource}/toggle         -> toggle
| POST   /datasources/{datasource}/test-connection -> testConnection
|
*/
Route::resource('datasources', DatasourceController::class);
Route::post('datasources/{datasource}/toggle', [DatasourceController::class, 'toggle'])->name('datasources.toggle');
Route::post('datasources/{datasource}/test-connection', [DatasourceController::class, 'testConnection'])->name('datasources.test-connection');