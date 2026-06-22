<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('When')
                    ->dateTime(),
                TextEntry::make('event')
                    ->badge(),
                TextEntry::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextEntry::make('auditable_id')
                    ->label('Record ID'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('System'),
                TextEntry::make('ip_address')
                    ->label('IP Address'),
                KeyValueEntry::make('old_values')
                    ->label('Previous Values')
                    ->columnSpanFull(),
                KeyValueEntry::make('new_values')
                    ->label('New Values')
                    ->columnSpanFull(),
            ]);
    }
}
