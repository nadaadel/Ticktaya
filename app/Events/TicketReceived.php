<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\RequestedTicket;
use App\Notification;
use App\NotifyType;

class TicketReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $request_id;
    public $notification_id;
    public $related_id;
    public $notify_type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($request_id,$is_sold,$notify_type)
    {
        $this->request_id = $request_id;
        $request=RequestedTicket::find($this->request_id);
        if($is_sold==1){
        $this->message = "Your ticket {$request->ticket()->name} has been sold successfully to {$request->requested_user()->name}, Thank you";
        }
        else
        {
        $this->message = "Your ticket {$request->ticket()->name} hasn't been delivered to {$request->requested_user()->name}";
        }
        $id=$request->ticket()->user->id;
        $notify_type_id=NotifyType::where('type','=',$notify_type)->first()->id;
        $notification=Notification::create([
            'user_id' => $id,
            'notify_type_id' => $notify_type_id,
            'message'=>$this->message,
            'related_id'=>$request->ticket()->id
        ]);
        $this->notification_id=$notification->id;
        $this->related_id=$request->ticket()->id;
        $this->notify_type=$notify_type;

    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $request=RequestedTicket::find($this->request_id);
        return ['ticket-received_'.$request->ticket()->user->id];
    }
}
