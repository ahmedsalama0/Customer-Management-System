<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $customers = Customer::when($request->has('search'), function($query) use ($request) {
            $query->where('first_name', 'LIKE', "%$request->search%")
            ->orWhere('last_name', 'LIKE', "%$request->search%")
            ->orWhere('email', 'LIKE', "%$request->search%")
            ->orWhere('phone', 'LIKE', "%$request->search%")
            ->orWhere('bank_account_number', 'LIKE', "%$request->search%")
            ->orWhere('about', 'LIKE', "%$request->search%");
        })->orderBy('id', $request->has('order') && $request->order === 'asc' ? 'ASC' : 'DESC')->get();
        return view('customer.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerStoreRequest $request)
    {

        $customer = new Customer();
        if($request->hasFile('image')) {
            // Local Storage
            // DB
            // 1. GET Image
            $image = $request->file('image');
            // 2. Store it locally
            $fileName =  $image->store('', 'public'); // public is the disk name
            $filePath = '/uploads/' . $fileName;
            // 3. Store the path of it in the DB
            $customer->image = $filePath;
        }

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->bank_account_number = $request->bank_account_number;
        $customer->about = $request->about;

        // Save the data in DB
        $customer->save();

        return redirect()->route('customers.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customer.show', ['customer' => $customer]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customer.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerStoreRequest $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        if($request->hasFile('image')) {
            // ... (local storage & DB)
            // if it has image delete the previous image from local storage
            File::delete(public_path($customer->image));
            // new file handling
            $image = $request->file('image');
            $fileName = $image->store('', 'public'); // storing it locally
            $filePath = '/uploads/' . $fileName;
            $customer->image = $filePath;
        }

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->bank_account_number = $request->bank_account_number;
        $customer->about = $request->about;

        $customer->save();

        return redirect()->route('customers.index');
    }

    public function trashIndex(Request $request) {

        // here in this method basically we load trashed data
        $customers = Customer::when($request->has('search'), function($query) use ($request) {
            $query->where('first_name', 'LIKE', "%$request->search%")
            ->orWhere('last_name', 'LIKE', "%$request->search%")
            ->orWhere('phone', 'LIKE', "%$request->search%")
            ->orWhere('email', 'LIKE', "%$request->search%")
            ->orWhere('bank_account_number', 'LIKE', "%$request->search%");
        })->orderBy('id', $request->has('order') && $request->order === 'asc' ? 'ASC' : 'DESC')->onlyTrashed()->get();
        return view('customer.trash', compact('customers'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Soft delete
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('customers.index');

        // Basic Deletion
        // $customer = Customer::findOrFail($id);
        // // before we delete a user from a DB we have to delete all his related file in DB.
        // File::delete(public_path($customer->image)); // deletion from locsl_storage
        // $customer->delete(); // deletion from DB
        // return redirect()->route('customers.index');
    }

    public function restore (string $id) {
        Customer::onlyTrashed()->findOrFail($id)->restore();
        return redirect()->back();
    }

    public function forceDestroy(string $id) {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        // we have to delete file from local storage first then from DB
        File::delete(public_path($customer->image));
        $customer->forceDelete();
        return redirect()->back();
    }
}