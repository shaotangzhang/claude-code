{{-- Scaffold view. --}}
<h1>Profile</h1>
@auth <p>{{ auth()->user()->name }} — {{ auth()->user()->email }}</p> @endauth
