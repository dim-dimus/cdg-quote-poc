<div class="mx-auto mt-16 max-w-sm">
    <div class="mb-6 text-center">
        <span class="rounded bg-slate-900 px-2 py-1 text-white">CDG</span>
        <h1 class="mt-3 text-xl font-semibold">Front Desk sign in</h1>
    </div>

    <form wire:submit="login" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" autofocus
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full rounded bg-slate-900 py-2 font-medium text-white hover:bg-slate-800">
            Sign in
        </button>
    </form>
</div>
