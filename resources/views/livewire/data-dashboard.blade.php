    <div class="container mx-auto space-y-6">
        <!-- Grid: 3 Columns -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Card 1: Data Master -->
            <div
                class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col h-full">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Data Master</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
                    <!-- Donut Chart -->
                    <div
                        class="bg-white dark:bg-zinc-900 rounded-lg p-4 shadow border border-gray-200 dark:border-gray-700 h-full flex items-center justify-center max-h-[20rem]">
                        <div id="donut-chart" class="h-full w-full"></div>
                    </div>
                    <!-- Info Boxes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-center text-gray-600 dark:text-gray-300">
                        @foreach ([['Satuan', $unitCount, 'units.index'], ['Merk', $brandCount, 'brands.index'], ['Supplier', $supplierCount, 'suppliers.index'], ['Barang', $itemCount, 'items.index']] as [$label, $count, $route])
                            <a href="{{ route($route) }}">
                                <div
                                    class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-lg shadow hover:ring-2 hover:ring-blue-500 transition">
                                    <p class="text-sm">Total {{ $label }}</p>
                                    <p class="text-2xl font-bold">{{ $count }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Card 2: Barang per Supplier -->
            <div
                class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col h-full">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Barang per Supplier</h2>
                <div class="flex-1 flex items-center justify-center max-h-[20rem]">
                    <div id="supplier-item-chart" class="h-full w-full"></div>
                </div>
            </div>

            <!-- Card 3: Transaksi Masuk -->
            <div
                class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col h-full">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Transaksi Masuk</h2>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg mb-4">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        @foreach ([['Pending', $pendingIn, 'orange'], ['Approved', $approvedIn, 'teal'], ['Total', $totalIn, 'blue']] as [$label, $val, $color])
                            <div class="bg-{{ $color }}-50 dark:bg-gray-600 rounded-lg p-3">
                                <p class="text-sm text-{{ $color }}-600 dark:text-{{ $color }}-300">
                                    {{ $label }}</p>
                                <p
                                    class="text-xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-300">
                                    {{ $val }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex-1 flex items-center justify-center max-h-[20rem]">
                    <div id="radial-chart" class="h-full w-full"></div>
                </div>
            </div>
        </div>

        <!-- Row: Coming Soon -->
        <div
            class="bg-white dark:bg-zinc-800 rounded-xl shadow border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col h-full">
            <div class="flex justify-between items-start mb-5">
                <div>
                    <h5 class="text-3xl font-bold text-gray-900 dark:text-white pb-2">
                        Rp {{ number_format($totalTransactionCount, 0, ',', '.') }} <span
                            class="text-sm font-medium text-gray-500 dark:text-gray-400">IDR</span>
                    </h5>
                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">Berdasarkan Type</p>
                </div>
                <div class="flex items-center space-x-2">
                    <select wire:model.live="chartRange"
                        class="text-sm px-3 py-1.5 rounded-md transition-colors
                        bg-gray-50 text-gray-800 border border-neutral-300
                        dark:bg-zinc-700 dark:text-white dark:border-neutral-600
                        focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:border-zinc-400
                        dark:focus:ring-zinc-500 dark:focus:border-zinc-500">
                        <option value="today">Hari ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="last_7_days">7 Hari Terakhir</option>
                        <option value="last_30_days">30 Hari Terakhir</option>
                        <option value="last_90_days">90 Hari Terakhir</option>
                    </select>
                </div>
            </div>

            <div class="flex-1">
                <div id="data-labels-chart" wire:ignore class="h-5 w-full"></div>
            </div>

            <div class="flex justify-between items-center pt-5 border-t mt-5 dark:border-gray-600">
                <a href="{{ route('reportstock.index') }}"
                    class="uppercase text-sm font-semibold inline-flex items-center rounded-lg text-blue-600 hover:text-blue-700 dark:hover:text-blue-500  hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2">
                    Laporan Transaksi
                    <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                </a>
            </div>
        </div>

    </div>
    @push('scripts')
        <script>
            function initChart(id, options) {
                const el = document.getElementById(id);
                if (!el || typeof ApexCharts === 'undefined') return;
                if (el._apexChart) el._apexChart.destroy();
                const chart = new ApexCharts(el, options);
                chart.render();
                el._apexChart = chart;
            }

            function renderDonutChart() {
                initChart("donut-chart", {
                    series: [{{ $unitCount }}, {{ $brandCount }}, {{ $supplierCount }}, {{ $itemCount }}],
                    labels: ["Satuan", "Merk", "Supplier", "Barang"],
                    chart: {
                        type: 'donut',
                        height: '100%',
                        width: '100%',
                        sparkline: {
                            enabled: false
                        }
                    },
                    legend: {
                        position: 'bottom'
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' Data'
                                    }
                                }
                            }
                        }
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                });
            }

            function renderSupplierChart() {
                const data = {!! json_encode([
                    'series' => array_column($itemsPerSupplier, 'count'),
                    'labels' => array_column($itemsPerSupplier, 'name'),
                ]) !!};

                initChart("supplier-item-chart", {
                    series: data.series,
                    labels: data.labels,
                    chart: {
                        type: 'donut',
                        height: '100%',
                        width: '100%',
                        sparkline: {
                            enabled: false
                        }
                    },
                    colors: ["#6366F1", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6", "#0EA5E9", "#E879F9", "#F43F5E"],
                    legend: {
                        position: "bottom"
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: false,
                                    total: {
                                        show: true,
                                        label: "Total",
                                        formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) + " Barang"
                                    }
                                }
                            }
                        }
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                });
            }

            function renderRadialChart() {
                const approved = @json($approvedIn);
                const pending = @json($pendingIn);
                const total = approved + pending;

                initChart("radial-chart", {
                    series: [
                        total ? (approved / total) * 100 : 0,
                        total ? (pending / total) * 100 : 0
                    ],
                    labels: ["Approved", "Pending"],
                    colors: ["#16BDCA", "#FDBA8C"],
                    chart: {
                        type: "radialBar",
                        height: '100%',
                        width: '100%',
                        sparkline: {
                            enabled: false
                        }
                    },
                    plotOptions: {
                        radialBar: {
                            track: {
                                background: '#E5E7EB'
                            },
                            hollow: {
                                margin: 0,
                                size: "30%"
                            },
                            dataLabels: {
                                show: false
                            }
                        }
                    },
                    legend: {
                        show: true,
                        position: "bottom",
                        fontFamily: "Inter, sans-serif"
                    },
                    tooltip: {
                        y: {
                            formatter: val => val.toFixed(0) + '%'
                        }
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                });
            }

            function renderTransactionChart(chartData) {
                const chartEl = document.getElementById("data-labels-chart");
                if (!chartEl || typeof ApexCharts === 'undefined') return;

                if (!chartData || Object.keys(chartData).length === 0) {
                    // console.warn('⚠️ Tidak ada data chart yang diberikan:', chartData);
                    return;
                }

                // console.log('🎯 Rendering chart dengan:', chartData);

                if (chartEl._apexChart) chartEl._apexChart.destroy();

                const formatRupiah = val => new Intl.NumberFormat("id-ID", {
                    style: "currency",
                    currency: "IDR",
                    minimumFractionDigits: 0
                }).format(val);

                const seriesData = Object.entries(chartData).map(([label, value], index) => ({
                    name: label,
                    data: [value],
                    color: ['#1A56DB', '#22C55E', '#F59E0B', '#6B7280', '#EF4444'][index % 5],
                }));
                // console.log('📊 seriesData:', seriesData);   

                const chart = new ApexCharts(chartEl, {
                    chart: {
                        type: 'bar',
                        height: 250,
                        animations: {
                            enabled: false
                        },
                        toolbar: {
                            show: false
                        }
                    },
                    series: seriesData,
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '50%'
                        }
                    },
                    dataLabels: {
                        enabled: true
                    },
                    xaxis: {
                        categories: ["Total"],
                        labels: {
                            show: false
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        show: false
                    },
                    tooltip: {
                        y: {
                            formatter: formatRupiah
                        }
                    },
                    legend: {
                        show: true,
                        position: 'bottom'
                    },
                    fill: {
                        opacity: 1
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                });

                chart.render();
                chartEl._apexChart = chart;
            }

            function renderAllCharts() {
                renderDonutChart();
                renderSupplierChart();
                renderRadialChart();
            }

            document.addEventListener("DOMContentLoaded", () => {
                renderAllCharts();
                renderTransactionChart(@json($transactionChartData));
            });

            // ✅ Event Livewire setelah komponen ter-load (satu kali)
            document.addEventListener('livewire:init', () => {
                Livewire.on('chartRangeUpdated', ({
                    chartData
                }) => {
                    console.log('✅ Chart updated with data:', chartData);
                    renderTransactionChart(chartData); // render ulang dengan data terbaru
                });
            });

            // ✅ Navigasi antar halaman Livewire SPA
            document.addEventListener("livewire:navigated", () => {
                setTimeout(() => {
                    renderAllCharts();
                    renderTransactionChart(
                        @json($transactionChartData)); // ⛳️ render ulang chart transaksi juga
                }, 50);
            });
        </script>
    @endpush
