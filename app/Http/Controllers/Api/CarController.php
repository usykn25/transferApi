<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Car\CarResource;
use App\Models\Car;
use App\Traits\ApiTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
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
                'transfer_start_time' => 'sometimes|date|date_format:d.m.Y H:i:s|before:transfer_finish_time',
                'transfer_finish_time' => 'sometimes|date|date_format:d.m.Y H:i:s|after::transfer_finish_time'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $cars = Car::
            when($request->filled('transfer_start_time'), function (Builder $query) use ($request) {
                $query->availableBetween($request->input('transfer_start_time'), $request->input('transfer_finish_time'));
            })
                ->when($request->filled('search'), function (Builder $query) use ($request) {
                    $query->where('model', 'like', "%" . $request->input('search') . "%")
                        ->orWhere('plaka', 'like', "%" . $request->input('search') . "%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $currenPage);
            return $this->apiSuccessResponse([
                'cars' => CarResource::collection($cars)]);
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
                'plaka' => ['required', 'string', 'regex:/^[0-9]{2}[A-Z]+[0-9]{3,}$/', 'unique:cars,plaka'],
                'model' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $car = Car::create($validator->validated());

            return $this->apiSuccessResponse(['car' => new CarResource($car)], Response::HTTP_CREATED, 'Car craeted successfully');
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
            $car = Car::findOrFail($id);

            return $this->apiSuccessResponse(['car' => new CarResource($car)]);

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
            $car = Car::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'plaka' => ['required', 'string', 'regex:/^[0-9]{2}[A-Z]+[0-9]{3,}$/', 'unique:cars,plaka,' . $id],
                'model' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $car->update($validator->validated());

            $car = Car::findOrFail($id);

            return $this->apiSuccessResponse(['car' => new CarResource($car)], Response:: HTTP_OK, 'Car was updated successfully');

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

            $car = Car::findOrFail($id);

            $car->delete();

            return $this->apiSuccessResponse(null, Response::HTTP_OK, 'Car was deleted successfully');
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }
}
