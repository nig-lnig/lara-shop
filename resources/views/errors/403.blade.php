@extends('errors.layout')
@section('code', '403')
@section('title', 'Accès refusé')
@section('message', $exception->getMessage() ?: 'Vous n’avez pas le droit d’accéder à cette page.')
