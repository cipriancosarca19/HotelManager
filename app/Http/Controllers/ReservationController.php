<?php

namespace App\Http\Controllers;

use App\Http\Interfaces\ManageTableInterface;
// TODO
use App\Http\Requests\GuestRequest;
use App\Http\Requests\ReservationSearchRequest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Services\GuestTableService;
use App\Services\ReservationTableService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReservationController extends Controller implements ManageTableInterface
{
    protected $reservationTableService;

    public function __construct(ReservationTableService $reservationTableService)
    {
        $this->reservationTableService = $reservationTableService;
    }

    public function index()
    {
        $title = trans('general.reservations');

        $dataset = Reservation::select('id', 'room_id', 'guest_id', 'date_start', 'date_end', 'people')
            ->with('guest:id,first_name,last_name')
            ->with('room:id,number')
            ->paginate($this->getItemsPerPage());

        if ($dataset->isEmpty()) {
            $this->addFlashMessage(trans('general.no_reservations_in_database'), 'alert-danger');
        }

        $viewData = [
            'columns'       => $this->reservationTableService->getColumns(),
            'dataset'       => $dataset,
            'routeName'     => $this->reservationTableService->getRouteName(),
            'title'         => $title,
            // TODO
            'deleteMessage' => mb_strtolower(trans('general.reservation')).' '.mb_strtolower(trans('general.number')),
        ];

        return view('list', $viewData);
    }

    public function chooseGuest(GuestTableService $guestTableService)
    {
        $title = trans('general.choose_guest');

        $dataset = Guest::select('id', 'first_name', 'last_name', 'address', 'zip_code', 'place', 'PESEL', 'contact')
            ->paginate($this->getItemsPerPage());

        if ($dataset->isEmpty()) {
            $this->addFlashMessage(trans('general.no_guests_in_database'), 'alert-danger');
        }

        $viewData = [
            'columns'         => $guestTableService->getColumns(),
            'dataset'         => $dataset,
            'routeName'       => $guestTableService->getRouteName(),
            'title'           => $title,
            'routeChooseName' => $this->reservationTableService->getRouteName().'.search_rooms',
        ];

        return view('list', $viewData);
    }

    public function searchRooms($guestId)
    {
        $dataset = new Reservation();
        $dataset->guest_id = $guestId;
        $title = trans('navigation.reservation_search_rooms');
        $submitRoute = route($this->reservationTableService->getRouteName().'.post_choose_room', $guestId);

        $viewData = [
            'dataset'     => $dataset,
            'fields'      => $this->getSearchFields(),
            'title'       => $title,
            'submitRoute' => $submitRoute,
        ];

        return view('addedit', $viewData);
    }

    public function reservationSearch(ReservationSearchRequest $request, $guestId = null)
    {
        try {
            $object = Guest::findOrFail($guestId);
        } catch (ModelNotFoundException $e) {
            return $this->returnBack([
                'message'     => trans('general.object_not_found'),
                'alert-class' => 'alert-danger',
            ]);
        }

        dd($request->all());

        return redirect()->route($this->reservationTableService->getRouteName().'.index')
            ->with([
                'message'     => trans('general.saved'),
                'alert-class' => 'alert-success',
            ]);
    }

    // TODO
    public function store(GuestRequest $request, $objectId = null)
    {
        if ($objectId === null) {
            $object = new Reservation();
        } else {
            try {
                $object = Reservation::findOrFail($objectId);
            } catch (ModelNotFoundException $e) {
                return $this->returnBack([
                    'message'     => trans('general.object_not_found'),
                    'alert-class' => 'alert-danger',
                ]);
            }
        }

        $object->fill($request->all());
        $object->save();

        return redirect()->route($this->reservationTableService->getRouteName().'.index')
            ->with([
                'message'     => trans('general.saved'),
                'alert-class' => 'alert-success',
            ]);
    }

    public function delete($objectId)
    {
        Reservation::destroy($objectId);
        $data = ['class' => 'alert-success', 'message' => trans('general.deleted')];

        return response()->json($data);
    }

    // TODO
    public function showAddEditForm($objectId = null)
    {
        if ($objectId === null) {
            $dataset = new Reservation();
            $title = trans('navigation.add_reservation');
            $submitRoute = route($this->reservationTableService->getRouteName().'.postadd');
        } else {
            try {
                $dataset = Reservation::select('id', 'room_id', 'guest_id', 'date_start', 'date_end', 'people')
                ->with('guest:id,first_name,last_name')
                ->with('room:id,number')
                ->findOrFail($objectId);
            } catch (ModelNotFoundException $e) {
                return $this->returnBack([
                    'message'     => trans('general.object_not_found'),
                    'alert-class' => 'alert-danger',
                ]);
            }

            $title = trans('navigation.edit_reservation');
            $submitRoute = route($this->reservationTableService->getRouteName().'.postedit', $objectId);
        }

        $viewData = [
            'dataset'     => $dataset,
            'fields'      => $this->getFields(),
            'title'       => $title,
            'submitRoute' => $submitRoute,
            'routeName'   => $this->reservationTableService->getRouteName(),
        ];

        return view('addedit', $viewData);
    }

    // TODO
    public function getSearchFields()
    {
        return [
            [
                'id'    => 'guest',
                'title' => trans('general.guest'),
                'value' => function (Reservation $data) {
                    return $data->guest->full_name;
                },
                'optional' => [
                    'disabled' => 'disabled',
                ],
            ],
            [
                'id'    => 'date_start',
                'title' => trans('general.reservation_date_of_stay_start'),
                'value' => function (Reservation $data) {
                    return $data->date_start;
                },
                'type'     => 'date',
                /*'optional' => [
                    'required' => 'required',
                ],*/
            ],
            [
                'id'    => 'date_end',
                'title' => trans('general.reservation_date_of_stay_start_end'),
                'value' => function (Reservation $data) {
                    return $data->date_end;
                },
                //'type'     => 'date',
                /*'optional' => [
                    'required' => 'required',
                ],*/
            ],
            [
                'id'    => 'people',
                'title' => trans('general.number_of_people'),
                'value' => function () {
                    // TODO
                    return 1;
                },
                'type'     => 'number',
                /*'optional' => [
                    'required' => 'required',
                ],*/
            ],
        ];
    }

    // TODO
    public function getFields()
    {
        return [
            [
                'id'    => 'first_name',
                'title' => trans('general.first_name'),
                'value' => function (Reservation $data) {
                    return $data->first_name;
                },
                'optional' => [
                    'required' => 'required',
                ],
            ],
            [
                'id'    => 'last_name',
                'title' => trans('general.last_name'),
                'value' => function (Reservation $data) {
                    return $data->last_name;
                },
                'optional' => [
                    'required' => 'required',
                ],
            ],
            [
                'id'    => 'address',
                'title' => trans('general.address'),
                'value' => function (Reservation $data) {
                    return $data->address;
                },
                'optional' => [
                    'required' => 'required',
                ],
            ],
            [
                'id'    => 'zip_code',
                'title' => trans('general.zip_code'),
                'value' => function (Reservation $data) {
                    return $data->zip_code;
                },
                'optional' => [
                    'required'    => 'required',
                    'placeholder' => '00-000',
                ],
            ],
            [
                'id'    => 'place',
                'title' => trans('general.place'),
                'value' => function (Reservation $data) {
                    return $data->place;
                },
                'optional' => [
                    'required' => 'required',
                ],
            ],
            [
                'id'    => 'PESEL',
                'title' => trans('general.PESEL'),
                'value' => function (Reservation $data) {
                    return $data->PESEL;
                },
                'optional' => [
                    'required'    => 'required',
                    'placeholder' => '12345654321',
                ],
            ],
            [
                'id'    => 'contact',
                'title' => trans('general.contact'),
                'value' => function (Reservation $data) {
                    return $data->contact;
                },
                'type'     => 'textarea',
                'optional' => [
                    'placeholder' => trans('general.contact_placeholder'),
                ],
            ],
        ];
    }
}
