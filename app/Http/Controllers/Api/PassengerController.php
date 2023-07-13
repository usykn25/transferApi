<?php

namespace App\Http\Controllers\Api;

use App\Enums\PassengerType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Driver\DriverResource;
use App\Http\Resources\Api\Passenger\PassengerResource;
use App\Models\Passenger;
use App\Traits\ApiTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class PassengerController extends Controller
{
    use ApiTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            $perPage = request()->input('per_page') ?? 10;
            $currenPage = request()->input('current_page') ?? 1;


            $validator = Validator::make($request->all(), [
                'passenger_type' => ['required', 'string', new Enum(PassengerType::class)]
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }


            $passengers = Passenger::when($request->filled('search'), function (Builder $query) use ($request) {
                $query->where('name', 'like', "%" . $request->input('search') . "%")
                    ->orWhere('surname', 'like', "%" . $request->input('search') . "%")
                    ->orWhere('phone', 'like', "%" . $request->input('search') . "%")
                    ->orWhere('passenger_type', 'like', "%" . $request->input('search') . "%");
            })
                ->when($request->filled('passenger_type'),function (Builder $query) use ($request) {
                    $query->where('passenger_type',$request->input('passenger_type'));
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $currenPage);
            return $this->apiSuccessResponse([
                'passengers' => PassengerResource::collection($passengers)]);
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3',
                'surname' => 'required|string|min:3',
                'phone' => ['required', 'string', 'regex:/^\+90[0-9]{10}$/'],
                'passenger_type' => ['required', 'string', new Enum(PassengerType::class)]
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }


            $passenger = Passenger::create($validator->validated());

            return $this->apiSuccessResponse(['passenger' => new PassengerResource($passenger)], Response::HTTP_CREATED, 'Passenger created successfully');
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $passenger = Passenger::findOrFail($id);

            return $this->apiSuccessResponse(['passenger' => new PassengerResource($passenger)]);

        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $passenger = Passenger::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3',
                'surname' => 'required|string|min:3',
                'phone' => ['required', 'string', 'regex:/^\+90[0-9]{10}$/'],
                'passenger_type' => ['required', 'string', new Enum(PassengerType::class)]
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $passenger->update($validator->validated());

            $passenger = Passenger::findOrFail($id);

            return $this->apiSuccessResponse(['car' => new PassengerResource($passenger), Response:: HTTP_OK, 'Passenger was updated successfully']);

        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $passenger = Passenger::findOrFail($id);

            $passenger->delete();

            return $this->apiSuccessResponse(null, Response::HTTP_OK, 'Passenger was deleted successfully');
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }
}
