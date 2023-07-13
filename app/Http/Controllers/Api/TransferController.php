<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Transfer\TransferResource;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Traits\ApiTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function Illuminate\Events\queueable;

class TransferController extends Controller
{
    use ApiTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $perPage = request()->input('per_page') ?? 10;
            $currenPage = request()->input('current_page') ?? 1;

            $transfers = Transfer::with('passengers', 'driver', 'car')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currenPage);
            return $this->apiSuccessResponse([
                'transfers' => TransferResource::collection($transfers)]);
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
                'passengers' => 'required|array',
                'passengers.*' => 'required|exists:passengers,id',
                'car_id' => 'required|exists:cars,id',
                'driver_id' => 'required|exists:drivers,id',
                'transfer_start_time' => 'sometimes|date|date_format:d.m.Y H:i:s|before:transfer_finish_time',
                'transfer_finish_time' => 'sometimes|date|date_format:d.m.Y H:i:s|after::transfer_finish_time',
                'transfer_start_place' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $validatedData = $validator->validated();

            $transferID = DB::transaction(function () use ($validatedData) {
                $transfer = Transfer::create([
                    'car_id' => $validatedData['car_id'],
                    'driver_id' => $validatedData['driver_id'],
                    'transfer_start_time' => Carbon::parse($validatedData['transfer_start_time']),
                    'transfer_finish_time' => Carbon::parse($validatedData['transfer_finish_time']),
                    'transfer_start_place' => $validatedData['transfer_start_place'],
                ]);

                $transferDetails = [];
                $passengerIds = $validatedData['passengers'];
                foreach ($passengerIds as $passengerId) {
                    $transferDetails[] = new TransferDetail([
                        'passenger_id' => $passengerId,
                    ]);
                }
                $transfer->transferDetails()->saveMany($transferDetails);

                return $transfer->id;
            });

            $transfer = Transfer::with('transferDetails', 'car', 'driver')->findOrFail($transferID);

            return $this->apiSuccessResponse(['transfer' => new TransferResource($transfer)], Response::HTTP_CREATED, 'Transfer created successfully');

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
            $transfer = Transfer::findOrFail($id);

            return $this->apiSuccessResponse(['transfer' => new TransferResource($transfer)]);
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

            $validator = Validator::make($request->all(), [
                'passengers' => 'required|array',
                'passengers.*' => 'required|exists:passengers,id',
                'car_id' => 'required|exists:cars,id',
                'driver_id' => 'required|exists:drivers,id',
                'transfer_start_time' => 'sometimes|date|date_format:d.m.Y H:i:s|before:transfer_finish_time',
                'transfer_finish_time' => 'sometimes|date|date_format:d.m.Y H:i:s|after::transfer_finish_time',
                'transfer_start_place' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validatorFails($validator->errors()->toArray());
            }

            $transfer = Transfer::findOrFail($id);

            DB::transaction(function () use ($transfer, $request) {
                $transfer->update([
                    'transfer_start_time' => Carbon::parse($request->input('transfer_start_time')),
                    'transfer_finish_time' => Carbon::parse($request->input('transfer_finish_time')),
                    'driver_id' => $request->input('driver_id'),
                    'car_id' => $request->input('car_id'),
                    'transfer_start_place' => $request->input('transfer_start_place'),
                ]);
                $transfer->transferDetails()->delete();

                foreach ($request->input('passengers') as $passengerId) {
                    $transferDetails[] = new TransferDetail([
                        'passenger_id' => $passengerId,
                    ]);
                }
                $transfer->transferDetails()->saveMany($transferDetails);
            });

            $transfer = Transfer::with('transferDetails', 'car', 'driver')->findOrFail($id);

            return $this->apiSuccessResponse(['transfer' => new TransferResource($transfer)], Response::HTTP_CREATED, 'Transfer updated successfully');

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

            $transfer = Transfer::findOrFail($id);

            $transfer->delete();

            return $this->apiSuccessResponse(null, Response::HTTP_OK, 'Transfer deleted was successfully');

        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }
}
