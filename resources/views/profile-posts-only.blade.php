<div class="list-group">
    @foreach($posts as $singlePost)
    <x-post :singlePost="$singlePost" hideAuthor/>           
    @endforeach
  </div>