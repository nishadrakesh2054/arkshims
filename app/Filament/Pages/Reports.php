<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Reports\DispatchReportWidget;
use App\Filament\Widgets\Reports\FinishedGoodsMovementReportWidget;
use App\Filament\Widgets\Reports\ProductionReportWidget;
use App\Filament\Widgets\Reports\RawMovementReportWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Reports Center';

    protected static ?string $title = 'Reports Center';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reports';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Reports')
                    ->tabs([
                        Tab::make('Production')
                            ->label('Production')
                            ->icon(Heroicon::OutlinedCog6Tooth)
                            ->schema([
                                Livewire::make(ProductionReportWidget::class),
                            ]),
                        Tab::make('Dispatch')
                            ->label('Dispatch & Sales')
                            ->icon(Heroicon::OutlinedTruck)
                            ->schema([
                                Livewire::make(DispatchReportWidget::class),
                            ]),
                        Tab::make('Raw Movement')
                            ->label('Raw Movement')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                Livewire::make(RawMovementReportWidget::class),
                            ]),
                        Tab::make('FG Movement')
                            ->label('Finished Goods')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
                                Livewire::make(FinishedGoodsMovementReportWidget::class),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Reports Center';
    }
}
