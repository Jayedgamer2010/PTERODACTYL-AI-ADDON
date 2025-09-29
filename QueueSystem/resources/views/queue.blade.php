@extends('layouts.admin')

@section('title')
    Queue System
@endsection

@section('content-header')
    <h1>Queue System <small>Manage your position in the queue</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Queue System</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Queue Status</h3>
            </div>
            <div class="box-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="text-center" style="padding: 40px 0;">
                    @if($isInQueue)
                        <div class="queue-position">
                            <h2 style="font-size: 48px; margin-bottom: 10px;">
                                #{{ $userQueue->position }}
                            </h2>
                            <p style="font-size: 18px; color: #666;">
                                You are currently in the queue
                            </p>
                            <p style="margin-top: 20px;">
                                <strong>Total in queue:</strong> {{ $totalInQueue }} users
                            </p>
                        </div>
                        
                        <form method="POST" action="{{ route('admin.queuesystem.leave') }}" style="margin-top: 30px;">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fa fa-sign-out"></i> Leave Queue
                            </button>
                        </form>
                    @else
                        <div class="no-queue">
                            <i class="fa fa-users" style="font-size: 72px; color: #ccc; margin-bottom: 20px;"></i>
                            <h3>You are not in the queue</h3>
                            <p style="color: #666; margin-bottom: 30px;">
                                Currently <strong>{{ $totalInQueue }}</strong> users waiting
                            </p>
                            
                            <form method="POST" action="{{ route('admin.queuesystem.join') }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-plus-circle"></i> Join Queue
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">How it works</h3>
            </div>
            <div class="box-body">
                <ul>
                    <li>Join the queue by clicking the "Join Queue" button</li>
                    <li>Your position will be automatically assigned</li>
                    <li>You can leave the queue at any time</li>
                    <li>Positions are updated automatically when users leave</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection