<?php

namespace App\Http\Controllers;
use DB;
use App\Ticket;
use App\User;
use Image;
use Auth;
use App\Category;
use App\RequestedTicket;
use App\SoldTicket;
use App\Tag;
use App\Notification;
use App\Events\TicketRequested;
use App\Events\TicketReceived;
use App\Events\StatusTicketRequested;
use Illuminate\Http\Request;

class TicketRequestsController extends Controller
{
    public function requestedTickets(){
        if(Auth::check() && Auth::user()->hasRole('admin')){

            $requestedTickets = RequestedTicket::paginate(6);
            return view('admin.tickets.requested' ,compact('requestedTickets'));
        }
          return view('notfound');
    }
    public function soldTickets(){
        if(Auth::check() && Auth::user()->hasRole('admin')){

            $soldTickets = SoldTicket::paginate(6);
            return view('admin.tickets.sold' ,compact('soldTickets'));
        }
          return view('notfound');
    }
    public function requestTicket(Request $request ,$id){
        $ticket = Ticket::find($id);

        $quantity=$request->quantity;
        if ($quantity <= $ticket->quantity && $quantity != 0){

        RequestedTicket::create([
            'ticket_id' => $id,
            'user_id' => $ticket->user_id,
            'requester_id' => Auth::user()->id,
            'quantity' => $request->quantity,
        ]);
       //  $request="true";

        // send request notification to ticket author
        event(new TicketRequested($id,'tickets'));
        return response()->json(['response' => 'ok']);

        }
        return response()->json(['quantity' =>$ticket->quantity]);
    }


    public function getUserRequests(Request $request){
        /** User Tickets received Requests */
        $userRequestsReceived =RequestedTicket::all()->where('user_id' , '=' , Auth::user()->id);
        $userTicketsSold = SoldTicket::all()->where('user_id' , '=' , Auth::user()->id);

        /** User Tickets Send Requests */
        $userRequestsWanted = RequestedTicket::all()->where('requester_id' , '=' , Auth::user()->id);
        $userTicketsBought = SoldTicket::all()->where('buyer_id' , '=' , Auth::user()->id);

         return view('tickets.userRequests' , compact('userRequestsReceived' , 'userTicketsSold' ,
        'userRequestsWanted' , 'userTicketsBought'));
        }


    public function acceptTicket($id , $requester_id){
        $user = User::find(Auth::user()->id);

        $request = $user->requestedTicket()->where('requester_id' , '=' , $requester_id)
                                            ->where('is_accepted','=',0)->first();
        $request->pivot->is_accepted = 1;
        $request->pivot->save();
        $requestedTicket=RequestedTicket::all()->where('requester_id' , '=' , $requester_id)->first();
        $is_accept=true;
        event(new StatusTicketRequested( $requestedTicket,$is_accept,"tickets"));

        return redirect('/tickets/requests');
    }

        public function editRequestedTicket(Request $request,$id){
            $ticket = Ticket::find($request->ticket_id);
            if ($request->quantity<=$ticket->quantity && $request->quantity!=0){
            $user = User::find($ticket->user_id);
            $requestTicket = $user->requestedTicket()->where('requester_id' , '=' ,Auth::user()->id)->first();
            $requestTicket->pivot->quantity =$request->quantity;
            $requestTicket->pivot->save();

            event(new TicketRequested($request->ticket_id,'tickets'));

            return response()->json(['response' => 'ok']);

            }
            return response()->json(['quantity' =>$ticket->quantity ]);


        }
        public function cancelTicketRequest($id , $requester_id){
            $user = User::find(Auth::user()->id);

            $allrequest = $user->requestedTicket()->where('requester_id' , '=' , $requester_id)
                                                  ->where('is_accepted','=',0)->first();



            $is_accept=false;
            $requestedTicket=RequestedTicket::all()->where('requester_id' , '=' , $requester_id)
                                                   ->where('is_accepted','=',0)->first();
            event(new StatusTicketRequested( $requestedTicket,$is_accept,'tickets'));
            $allrequest->pivot->delete();
            return redirect('/tickets/requests');
        }
        public function ticketSold($id){
            //ticke will not be sold if its quntity =0  beacuse
            //one ticket can be requested by one more user with different quantity
            $ticket = Ticket::find($id);
            $user=User::find(Auth::user()->id);
            $requested =  RequestedTicket::where([['ticket_id' , '=' , $id] ,
            ['requester_id' , '=' , Auth::user()->id] ,
            ['user_id' , '=' , $ticket->user_id]])->first();

            $requested->is_sold=1;
            $requested->save();
            $avaliableTickets = $ticket->quantity - $requested->quantity;

            if($avaliableTickets == 0){

                $ticket->is_sold = 1;
                $ticket->save();
                $ticket->quantity = 0;
                $ticket->save();
            }else{
                $ticket->quantity = $avaliableTickets;
                $ticket->save();
            }

            $ticket->save();
             SoldTicket::create([
             'ticket_id' => $id,
             'user_id' => $ticket->user_id,
             'buyer_id' => Auth::user()->id,
             'quantity' => $requested->quantity ,
            ]);
            event(new TicketReceived($requested->id,$ticket->is_sold,'tickets'));
           return redirect('/tickets/requests');
         }


         public function cancelTicketSold($id){
            $ticket = Ticket::find($id);
            $ticket->is_sold =0;
            $ticket->save();
            $requested =  RequestedTicket::where([['ticket_id' , '=' , $id] ,
            ['requester_id' , '=' , Auth::user()->id] ,
            ['user_id' , '=' , $ticket->user_id]])->get();
            $requested[0]->is_sold = 0;
            $requested[0]->save();

            event(new TicketReceived($requested[0]->id,0,'tickets'));
            $requested[0]->delete();
           return redirect('/tickets/requests');
         }


}
