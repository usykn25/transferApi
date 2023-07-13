<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Car\CarResource;
use App\Http\Resources\Api\Driver\DriverResource;
use App\Models\Car;
use App\Models\Driver;
use App\Traits\ApiTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
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

            $drivers = Driver::
            when($request->filled('transfer_start_time'), function (Builder $query) use ($request) {
                $query->availableBetween($request->input('transfer_start_time'), $request->input('transfer_finish_time'));
            })
                ->when($request->filled('search'), function (Builder $query) use ($request) {
                    $query->where('full_name', 'like', "%" . $request->input('search') . "%")
                        ->orWhere('tc', 'like', "%" . $request->input('search') . "%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $currenPage);
            return $this->apiSuccessResponse([
                'drivers' => DriverResource::collection($drivers)]);
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
                'full_name' => 'required|string',
                'tc' => 'required|regex:/^\d{11}$/|unique:drivers,tc'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $driver = Driver::create($validator->validated());

            return $this->apiSuccessResponse(['driver' => new DriverResource($driver)], Response::HTTP_CREATED, 'Driver craeted successfully');
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
            $driver = Driver::findOrFail($id);

            return $this->apiSuccessResponse(['driver' => new CarResource($driver)]);

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
            $driver = Driver::findOrFail($id);


            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string',
                'tc' => 'required|regex:/^\d{11}$/|unique:drivers,tc,' . $id,
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $driver->update($validator->validated());

            $driver = Driver::findOrFail($id);

            return $this->apiSuccessResponse(['driver' => new DriverResource($driver), Response:: HTTP_OK, 'Driver was updated successfully']);

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

            $driver = Driver::findOrFail($id);

            $driver->delete();

            return $this->apiSuccessResponse(null, Response::HTTP_OK, 'Driver was deleted successfully');
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }
}
