@if($page->hasPages())<div class="pagination"><span>@if($page->previousPageUrl())<a href="{{$page->previousPageUrl()}}">← Anterior</a>@endif</span><span>{{$page->currentPage()}} / {{$page->lastPage()}}</span><span>@if($page->nextPageUrl())<a href="{{$page->nextPageUrl()}}">Siguiente →</a>@endif</span></div>@endif

