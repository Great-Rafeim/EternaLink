<x-cemetery-layout>
    <div class="container mx-auto max-w-lg p-6 bg-gray-800 rounded-lg shadow-lg">

        <!-- Header with "Mark as Available" Button -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-white">Edit Plot #{{ $plot->plot_number }}</h1>
            
            @if ($plot->status === 'reserved' || $plot->status === 'occupied')
                <form action="{{ route('plots.markAvailable', $plot) }}" method="POST" onsubmit="return confirmMarkAvailable();" class="inline">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded font-semibold text-sm shadow-md">
                        Mark as Available
                    </button>
                </form>
            @endif
        </div>

        <!-- Tabs -->
        <div id="tabs" class="mb-6 flex border-b border-gray-600 overflow-x-auto">
            <button type="button" data-tab="plot" class="tab-btn px-4 py-2 border-b-2 font-semibold focus:outline-none border-indigo-500 text-indigo-400">Plot</button>
            <button type="button" data-tab="reservation" class="tab-btn ml-6 px-4 py-2 border-b-2 font-semibold focus:outline-none text-gray-400 hover:text-indigo-400">Reservation</button>
            <button type="button" data-tab="occupy" class="tab-btn ml-6 px-4 py-2 border-b-2 font-semibold focus:outline-none text-gray-400 hover:text-indigo-400">Occupy</button>
            <button type="button" data-tab="history" class="tab-btn ml-6 px-4 py-2 border-b-2 font-semibold focus:outline-none text-gray-400 hover:text-indigo-400">History</button>
        </div>

        <!-- Tab Contents -->
        <div id="tab-contents">
            <div data-tab-content="plot" class="tab-content text-white">
                @include('cemetery.plots.forms.first', ['plot' => $plot])
            </div>
            <div data-tab-content="reservation" class="tab-content text-white hidden">
                @include('cemetery.plots.forms.resform', ['plot' => $plot, 'reservation' => $plot->reservation])
            </div>
            <div data-tab-content="occupy" class="tab-content text-white hidden">
                @include('cemetery.plots.forms.ocuform', ['plot' => $plot, 'occupation' => $plot->occupation])
            </div>
            <div data-tab-content="history" class="tab-content text-white hidden">
                @include('cemetery.plots.forms.history', [
                    'plot' => $plot,
                    'reservationHistory' => $plot->reservationHistory,
                    'occupationHistory' => $plot->occupationHistory
                ])
            </div>
        </div>
    </div>

    <style>.hidden { display: none; }</style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('#tabs .tab-btn');
            const contents = document.querySelectorAll('#tab-contents .tab-content');

            function setActiveTab(tabName) {
                tabs.forEach(btn => {
                    btn.classList.toggle('border-indigo-500', btn.dataset.tab === tabName);
                    btn.classList.toggle('text-indigo-400', btn.dataset.tab === tabName);
                    btn.classList.toggle('text-gray-400', btn.dataset.tab !== tabName);
                });

                contents.forEach(content => {
                    content.classList.toggle('hidden', content.dataset.tabContent !== tabName);
                });
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
            });

            setActiveTab('plot');
        });

        function confirmMarkAvailable() {
            return confirm("This action will mark the plot as available and archive the current reservation and occupation info. Continue?");
        }
    </script>
</x-cemetery-layout>
