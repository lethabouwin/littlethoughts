<a href="/post/{{ $singlePost->id }}" class="list-group-item list-group-item-action">
    <img class="avatar-tiny" src={{$singlePost->userFromId->avatar}} />
    <strong>{{$singlePost->title}}</strong>
    <span class="text-muted small"> 
        @if(!isset($hideAuthor))
            by {{$singlePost->userFromId->username}}     
        @endif
    
        on {{$singlePost->created_at->format('d/m/Y')}}
    </span>
</a>    