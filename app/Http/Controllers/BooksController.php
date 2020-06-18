<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Facades\App\Books;
use Illuminate\support\Collection;

class BooksController extends Controller
{
    //
    public function __construct()
    {}

    /**
     * create the function to book add 
     * 
     * @param $Request $Request
     * @return Illuminate\Http\Response create Book
     */
    public function addBook(Request $request) {
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $book = Books::createBooks($data);
        return response()->json(['message' => 'Book added', 'id' =>$book->id], 201);
    }

    /**
     * create the function to get listofBooks
     * 
     * @return Illuminate\Http\Response
     */
    public function getListBook() {

        Cache::forget('books' . Auth::user()->id);
        $books = Cache::remember('books' . Auth::user()->id, (30), function () {
            $bb = Books::where('user_id', Auth::user()->id)->get();
            return $bb;
        });
    }

    /**
     * create the function for update books
     * 
     * @param Request $request
     * @return Illuminate\Http\Response 
     */
    public function updateBook(Request $request) {

        $data = $request->all();
        $books = Cache::get('books' . Auth::user()->id);
        $book = Books::where('id', $request->get('id'));
        $book->update(
            [
                'id' => $request->get('id'),
                'name' => $request->get('name'),
                'image' => $request->get('image'),
                'price' => $request->get('price'),
                'noOfBooks' => $request->get('noOfBooks'),
                'Availability' => $request->get('Availability'),
                'Description' => $request->get('Description'),
                'user_id' => $request->get('user_id'),
                'author_name' => $request->get('author_name'),
                'Ratings' => $request->get('Ratings'),
                'Reviews' => $request->get('Reviews'),
            ]
        );
        Cache::forget('Books' . Auth::user()->id);
        return response()->json(['message' => Books::with('authors')->where('id', $request->get('id'))->get()], 200);
    }

    /**
     * create the function for delete the book
     * 
     * @param Request $request 
     * @return Illuminate\Http\Response 
     */
    public function deletebook(Request $request) {

        $data = $request->all();
        if (Books::destroy($request->get('id')) > 0) {
            $books = Books::where('user_id', Auth::user()->id)->get();
            foreach ($books as $book) {
                if ($book->index > $data['index']) {
                    $book->index -= 1;
                    $book->save();
                }
            }
            Cache::forget('books' . Auth::user()->id);
            return response()->json(['message' => 'Book deleted'], 200);
        } else {
            return response()->json(['message' => 'Book not found'], 204);
        }
    }

    /**
     * create the function for search the book based name,price,
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function searchbooks(Request $request)
    {   
        $books = Books::all()->pluck('name','author_name');
        $filter = $books->filter(function($value,$key) use ($name, $author_name){
            return collect($filter->toArray())->$value('LIKE','%')
            ->only(['id','name','image','price','noOfBooks','user_id','Availability','Description','author_name','Reviews','Ratings']);
            $filter->all();
        });
    }

    /**
     * create the function to sort the book base price
     * 
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function sortbooks(Request $request) {
        $books = Books::all()->pluck('price');
        $sort  = $books->sortBy(function($value,$key) use($price) {
            return collect();
        });
    }
}
