<nav id="unlogged-navbar" class="navbar navbar-expand-lg navbar-light bg-dark">
    <a class="navbar-brand" href="#"><img src="../images/home/logo.png"></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
<!--
      <ul class="navbar-nav navbar-right">
        <li class="nav-item active pt-3">
-->
       <ul class="navbar-nav navbar-left">

        <li class="nav-item active pt-3">
            <a class="nav-link" href="{{URL::route('home')}}">HOME</a>
        </li>
        <li class="nav-item active pt-3">
            <a class="nav-link" href="{{URL::route('alltickets')}}">TICKETS</a>
        </li>
        <li class="nav-item pt-3">
            <a class="nav-link" href="{{URL::route('allevents')}}">EVENTS</a>
        </li>
        <li class="nav-item pt-3">
            <a class="nav-link " href="{{URL::route('allarticles')}}">BLOG</a>
        </li>

       @if (Auth::check())
       <li class="nav-item dropdown dropdown-notifications">
            <a href="#notifications-panel" class="dropdown-toggle nav-link pt-4" data-toggle="dropdown">
                    <i data-count="0" class="notification-icon ">
                            <i class="far fa-bell light" style="font-size: 20px;"></i></i>
            </a>
            <div class="dropdown-container">
                <ul class="dropdown-menu" style="
                width: 301px;
                height: 260px;
                border-width: 0 0 1px 0;
                margin-right: 0px;
            ">
                        <div class="dropdown-toolbar">
                        <div class="dropdown-toolbar-actions" style="text-align:right;height:24px;padding-right: 3px;">
                            <a id="readall" href="#">Mark all as read </a>
                        </div>
                        <div>
                        <ul id="dropdownmenu" style="
                        height: 200px;
                        overflow: auto;
                        padding-left: 0px;
                        margin-right: 0px;
                    "></ul>
                        </div>
                    </div>
                        <div class="dropdown-footer text-center">
                                <a href="/notifications">View All</a>
                     </div>
                </ul>

            </div>
        </li>
      <li class="nav-item active pt-1">
        <a class="nav-link " href="/users/{{Auth::user()->id}}"><button type="button" class="btn btn-outline-primary">Profile</button></a>
      </li>
      @role('admin')
      <li class="nav-item active pt-3">
        <a class="nav-link " href="/admin"><button type="button" class="btn btn-outline-primary">Admin Panel</button></a>
      </li>
     @endrole
      <li class="nav-item pt-3">
        <a class="nav-link " href="/logout">LOG OUT</a>
      </li>
      @else
      <li class="nav-item pt-3 pl-5">
              <a class="nav-link " href="{{URL::route('login')}}">LOGIN </a>
            </li>
            <li class="nav-item ">
              <a class="nav-link mt-1" href="{{URL::route('register')}}"><button type="button" class="btn btn-outline-light">REGISTER</button></a>
            </li>
    @endif
  </ul>
      <ul class="navbar-nav navbar-left">

      </ul>
  </div>
</nav>

@if(Auth::check())
<script type="text/javascript">
$(function () {
  var oldNotifications = {!! json_encode(Auth::user()->notifications->toArray()) !!};
  var CountoldNotifications = {!! json_encode(Auth::user()->notifications->where('is_seen','=',0)->count()) !!};
  var notificationsWrapper   = $('.dropdown-notifications');
  var notificationsToggle    = notificationsWrapper.find('a[data-toggle]');
  var notificationsCountElem = notificationsToggle.find('i[data-count]');
  //var notificationsCount     = parseInt(notificationsCountElem.data('count'));
  var notificationsCount=CountoldNotifications;
  var notifications          = notificationsWrapper.find('ul.dropdown-menu');
  Pusher.logToConsole = true;

  //** don't forget to change this **//
  var pusher = new Pusher('6042cdb1e9ffa998e5be', {
    encrypted: true,
    cluster:"mt1"
  });


  function updateNotificationCount(){
    notificationsCountElem.attr('data-count', notificationsCount);
    notificationsWrapper.find('.notif-count').text(notificationsCount);
    notificationsWrapper.show();
  }
function notificationsHtml(data,realtime){
          var existingNotifications = $('#dropdownmenu').html();
          var avatar = Math.floor(Math.random() * (71 - 20 + 1)) + 20;
          if(realtime){
                notificationsCount += 1;
                data.created_at=new Date(Date.now());
                data.is_seen=0;
                data.id=data.notification_id;
                console.log(data.notification_id);
          }
          //var date= data.created_at === undefined ? new Date(Date.now())  : data.created_at ;
          var newNotificationHtml = `
          <li class="notification active pl-1 pr-1">
             <div class="media">
                <div class="media-left">
                    <div class="media-object">
                        <img src="https://api.adorable.io/avatars/71/`+avatar+`.png" class="img-circle" alt="50x50" style="width: 50px; height: 50px;">
                        </div>
                        </div>
                        <div class="media-body">
                            <a notif-no="`+data.id+`"  class="notify-seen" style="cursor: pointer;"><strong style="color:black;" class="notification-title">`+data.message+`</strong></a>
                            <p class="notification-desc"></p>
                            <div class="notification-meta">
                                <small class="timestamp">`+data.created_at+`</small>
                                </div>
                                </div>
                                </div>
                                </li>`;
            $('#dropdownmenu').html(newNotificationHtml + existingNotifications);
            updateNotificationCount();

  }
  function bindChannel(channel,event) {
      channel.bind(event , function(notify){
          console.log(notify);
        notificationsHtml(notify,1);
        console.log(notify.is_accept)
        if(notify.is_accept == true){
            $('#edit').hide();
            $('#loginuser').hide();

        }
        else{
        $('#edit').hide();
        $('#loginuser').hide();
        $('#RequestTicket').show();

        }

        });
}
  $.each(oldNotifications.reverse(), function( i, val) {
    notificationsHtml(val,0);
});

 // make all is read
$(document).on('click','#readall',function(event){
    event.preventDefault();
            $.ajax({
                type: 'get',
                url: '/notifications/allread',
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if(response.res=='success'){
                        notificationsCount=0;
                        updateNotificationCount();
                    }
                }
            });
       });

    // change notifications status that was unseen to be seen
    $(document).on('click','.notify-seen',function(event){
        //event.preventDefault();
        notif_id=$(this).attr('notif-no');
                $.ajax({
                    type: 'get',
                    url: '/notifications/'+notif_id+'/edit',
                    data:{
                    '_token':'{{csrf_token()}}',
                    },
                    success: function (response) {
                        if(response.res=='unseen'){
                            notificationsCount-=1;
                            updateNotificationCount();
                        }
                        window.location = "/tickets/requests";
                    }

            });
       });
var ticketRequestChannel = pusher.subscribe('ticket-requested_{{ Auth::user()->id }}');
bindChannel(ticketRequestChannel,'App\\Events\\TicketRequested');
var ticketReceivedChannel= pusher.subscribe('ticket-received_{{ Auth::user()->id }}');
bindChannel(ticketReceivedChannel,'App\\Events\\TicketReceived');
var statusTicketrequested=pusher.subscribe('status-tickedrequest_{{ Auth::user()->id }}');
bindChannel(statusTicketrequested,'App\\Events\\StatusTicketRequested');

var eventSubscribersChannel = pusher.subscribe('event-subscriber_{{ Auth::user()->id }}');
bindChannel(eventSubscribersChannel,'App\\Events\\EventSubscribers');

var questionNotification=pusher.subscribe('question-notification_{{ Auth::user()->id }}');
bindChannel(questionNotification,'App\\Events\\Question');

var answerNotification=pusher.subscribe('answer-notification_{{ Auth::user()->id }}');
bindChannel(answerNotification,'App\\Events\\Answer');
});

</script>
@endif
