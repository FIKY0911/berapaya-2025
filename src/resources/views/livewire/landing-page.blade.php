<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Activities & Infographics</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 overflow-x-hidden">

  <!-- Header -->
  <header class="bg-white shadow-sm fixed top-0 left-0 w-full z-50">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        
        <!-- Logo + Brand -->
        <div class="flex items-center gap-2">
          <img src="/img/berapaya.jpg" alt="Logo" class="h-6 w-6 object-contain rounded-full">
          <a class="flex items-center gap-2 text-teal-600 font-bold text-lg" href="#">
            BerapaYa   
          </a>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:block">
          <ul class="flex items-center gap-6 text-sm font-medium">
            <li><a href="{{ url('/admin') }}" class="text-gray-600 hover:text-teal-600">Home</a></li>
            <li><a href="#" class="text-gray-600 hover:text-teal-600">Search</a></li>
            <li><a href="#" class="text-gray-600 hover:text-teal-600">History</a></li>
            <li><a href="#" class="text-gray-600 hover:text-teal-600">Profile</a></li>
          </ul>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-4">
          <button class="text-gray-600 hover:text-gray-800">
            🔔
          </button>
          <button class="text-gray-600 hover:text-gray-800">
            ⚙
          </button>
        </div>a
      </div>
    </div>
  </header>

  <!-- Main Section -->
  <main class="pt-24 pb-16 mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">

    <!-- Recent Activities -->
    <section class="mb-12">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activities</h2>
      <div class="bg-teal-600 text-white rounded-lg p-4 flex items-center justify-between shadow">
        <div>
          <p class="text-sm">25 Jun 2025</p>
          <h3 class="text-lg font-semibold">Nyeri kepala dan dada</h3>
          <p class="text-sm">Laboratorium Dasar, Konsultasi Dokter Spesialis</p>
        </div>
        <span class="text-2xl">➜</span>
      </div>
    </section>

    <!-- Infographics -->
    <section>
      <h2 class="text-xl font-bold text-gray-900 mb-6">Infografis</h2>
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        
        <!-- Item -->
        <article class="flex gap-4 bg-white rounded-lg shadow p-4">
          <img src="/img/rs.jpg" alt="Infografis" class="w-24 h-24 rounded object-cover">
          <div>
            <h3 class="font-semibold text-gray-800">Golongan Rumah Sakit di Indonesia</h3>
            <p class="text-sm text-gray-500">Sen, 7 Juli 2025</p>
          </div>
        </article>

        <article class="flex gap-4 bg-white rounded-lg shadow p-4">
          <img src="/img/bpjs.jpg" alt="Infografis" class="w-24 h-24 rounded object-cover">
          <div>
            <h3 class="font-semibold text-gray-800">Panduan Klaim BPJS Kesehatan</h3>
            <p class="text-sm text-gray-500">Sen, 7 Juli 2025</p>
          </div>
        </article>

        <article class="flex gap-4 bg-white rounded-lg shadow p-4">
          <img src="/img/kalkulator.jpg" alt="Infografis" class="w-24 h-24 rounded object-cover">
          <div>
            <h3 class="font-semibold text-gray-800">Perbandingan Biaya Rawat Jalan & Rawat Inap</h3>
            <p class="text-sm text-gray-500">Sen, 7 Juli 2025</p>
          </div>
        </article>

        <article class="flex gap-4 bg-white rounded-lg shadow p-4">
          <img src="/img/bpjs.jpg" alt="Infografis" class="w-24 h-24 rounded object-cover">
          <div>
            <h3 class="font-semibold text-gray-800">Apa yang Ditanggung BPJS vs Asuransi Swasta?</h3>
            <p class="text-sm text-gray-500">Sen, 7 Juli 2025</p>
          </div>
        </article>

      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-white border-t mt-12">
    <div class="mx-auto max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
      <p class="text-gray-500 text-sm">© 2025 MyHealth. All rights reserved.</p>
      <nav class="flex gap-6 text-sm text-gray-500">
        <a href="#" class="hover:text-teal-600">Privacy</a>
        <a href="#" class="hover:text-teal-600">Terms</a>
        <a href="#" class="hover:text-teal-600">Contact</a>
      </nav>
    </div>
  </footer>

</body>
</html>
