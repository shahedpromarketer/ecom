@extends('super-admin.layout')
@section('page-title', 'Announcements')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Send Form --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
            <h3 class="font-semibold text-gray-800 mb-4">Send Announcement</h3>
            <form method="POST" action="{{ route('super.announcements.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Title</label>
                    <input type="text" name="title" required maxlength="200"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Announcement title...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Message</label>
                    <textarea name="message" required rows="5"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="Write your message..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Target Audience</label>
                    <select name="target" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">All Tenants</option>
                        <option value="active">Active Tenants Only</option>
                        <option value="plan_basic">Basic Plan</option>
                        <option value="plan_pro">Pro Plan</option>
                        <option value="plan_enterprise">Enterprise Plan</option>
                    </select>
                </div>
                <button type="submit"
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                    Send Announcement
                </button>
            </form>
        </div>
    </div>

    {{-- Announcements List --}}
    <div class="lg:col-span-2 space-y-4">
        <h3 class="font-semibold text-gray-800">Sent Announcements</h3>

        @forelse($announcements as $ann)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="font-semibold text-gray-900">{{ $ann->title }}</h4>
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                            {{ $ann->target_label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $ann->message }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        Sent by {{ $ann->sender?->name ?? 'System' }} · {{ $ann->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('super.announcements.delete', $ann->id) }}"
                      onsubmit="return confirm('Delete this announcement?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition flex-shrink-0">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
            No announcements sent yet.
        </div>
        @endforelse

        @if($announcements->hasPages())
        <div>{{ $announcements->links() }}</div>
        @endif
    </div>
</div>
@endsection
