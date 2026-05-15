@extends('layouts.admin')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('breadcrumb')
    <span class="text-textsecondary">/</span>
    <span class="text-textprimary font-medium">Analytics</span>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

<form method="GET" action="{{ route('admin.analytics.index') }}"
      class="flex flex-wrap items-center gap-3 mb-6">
    @foreach(['today' => 'Today', '7' => 'Last 7 Days', '30' => 'Last 30 Days', '90' => 'Last 90 Days'] as $val => $label)
    <a href="{{ request()->fullUrlWithQuery(['range' => $val]) }}"
       class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors {{ $range == $val ? 'bg-accent text-white' : 'border border-border dark:border-gray-600 text-textsecondary dark:text-gray-400 hover:text-textprimary dark:hover:text-gray-200' }}">
        {{ $label }}
    </a>
    @endforeach
    <div class="flex items-center gap-2 ml-auto">
        <input type="date" name="from" value="{{ request('from') }}"
               class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <span class="text-xs text-textsecondary dark:text-gray-400">to</span>
        <input type="date" name="to" value="{{ request('to') }}"
               class="px-3 py-1.5 text-xs rounded-xl border border-border dark:border-gray-600 bg-white dark:bg-gray-700/50 text-textprimary dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary dark:focus:border-primary transition-all duration-200">
        <input type="hidden" name="range" value="custom">
        <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">Go</button>
    </div>
</form>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @php
    $kpis = [
        ['label' => 'Total Revenue',    'value' => '৳' . number_format($totalRevenue, 0),   'color' => 'text-green-600 dark:text-green-400'],
        ['label' => 'Total Orders',     'value' => number_format($totalOrders),              'color' => 'text-blue-600 dark:text-blue-400'],
        ['label' => 'Avg Order Value',  'value' => '৳' . number_format($avgOrderValue, 0),  'color' => 'text-indigo-600 dark:text-indigo-400'],
        ['label' => 'New Customers',    'value' => number_format($totalCustomers),            'color' => 'text-purple-600 dark:text-purple-400'],
        ['label' => 'Repeat Customers', 'value' => number_format($repeatCustomers),           'color' => 'text-accent'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-5">
        <p class="text-xs font-medium text-textsecondary dark:text-gray-400 mb-1">{{ $kpi['label'] }}</p>
        <p class="text-2xl font-bold {{ $kpi['color'] }}">{{ $kpi['value'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Revenue (Delivered Orders)</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Orders Volume</h3>
        <canvas id="ordersChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Top 10 Selling Products</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-border dark:border-gray-700">
                    <tr>
                        <th class="text-left pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="text-left pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                        <th class="text-right pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                        <th class="text-right pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border dark:divide-gray-700">
                    @forelse($topProducts as $i => $product)
                    <tr>
                        <td class="py-2 text-xs text-textsecondary dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="py-2 font-medium text-textprimary dark:text-gray-100 max-w-[180px] truncate">{{ $product->name }}</td>
                        <td class="py-2 text-right text-textsecondary dark:text-gray-400">{{ number_format($product->total_qty) }}</td>
                        <td class="py-2 text-right font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($product->revenue, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-4 text-center text-textsecondary dark:text-gray-400 text-sm">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Orders by Status</h3>
        <canvas id="statusChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Top 10 Customers</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-border dark:border-gray-700">
                    <tr>
                        <th class="text-left pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="text-right pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orders</th>
                        <th class="text-right pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border dark:divide-gray-700">
                    @forelse($topCustomers as $customer)
                    <tr>
                        <td class="py-2">
                            <p class="font-medium text-textprimary dark:text-gray-100">{{ $customer->user?->name ?? 'Guest' }}</p>
                            <p class="text-xs text-textsecondary dark:text-gray-400">{{ $customer->user?->phone ?? '' }}</p>
                        </td>
                        <td class="py-2 text-right text-textsecondary dark:text-gray-400">{{ $customer->orders_count }}</td>
                        <td class="py-2 text-right font-semibold text-textprimary dark:text-gray-100">৳{{ number_format($customer->total_spent, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="py-4 text-center text-textsecondary dark:text-gray-400 text-sm">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-border dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-heading font-semibold text-textprimary dark:text-gray-100 mb-4">Revenue by Payment Method</h3>
        <canvas id="paymentChart" height="200"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor   = isDark ? '#9ca3af' : '#6b7280';
    const gridColor   = isDark ? '#374151' : '#e5e7eb';
    const fontFamily  = 'Inter, sans-serif';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = fontFamily;

    const revenueData = @json($revenueByDate);
    const ordersData  = @json($ordersByDate);
    const statusData  = @json($ordersByStatus);
    const paymentData = @json($revenueByPayment);

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.date),
            datasets: [{
                label: 'Revenue (৳)',
                data: revenueData.map(d => d.revenue),
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { grid: { color: gridColor } }, x: { grid: { color: gridColor } } } }
    });

    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: ordersData.map(d => d.date),
            datasets: [{
                label: 'Orders',
                data: ordersData.map(d => d.orders),
                backgroundColor: isDark ? 'rgba(99,102,241,0.6)' : 'rgba(79,70,229,0.6)',
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { grid: { color: gridColor } }, x: { grid: { color: gridColor } } } }
    });

    const statusColors = ['#f59e0b','#3b82f6','#6366f1','#06b6d4','#22c55e','#ef4444','#8b5cf6'];
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
            datasets: [{ data: statusData.map(d => d.count), backgroundColor: statusColors, borderWidth: 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    const payColors = ['#22c55e','#3b82f6','#f59e0b'];
    new Chart(document.getElementById('paymentChart'), {
        type: 'pie',
        data: {
            labels: paymentData.map(d => d.payment_method.toUpperCase()),
            datasets: [{ data: paymentData.map(d => d.revenue), backgroundColor: payColors, borderWidth: 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
@endpush
