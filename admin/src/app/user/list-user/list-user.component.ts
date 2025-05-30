import { Component, OnInit } from "@angular/core";
import { AdminService } from "src/app/_services/admin.service";
import { ToastrService } from "ngx-toastr";
import { Router } from "@angular/router";

@Component({
  selector: "app-list-user",
  templateUrl: "./list-user.component.html",
  styleUrls: ["./list-user.component.css"],
})
export class ListUserComponent implements OnInit {
  constructor(
    private adminService: AdminService,
    private toastr: ToastrService,
    private router: Router
  ) {}

  usersList: any[] = [];

  ngOnInit() {
    this.loadUserList();
  }
  // userObj: any = {
  //   page: 1,
  //   limit: 10,
  //   search: "",
  //   status: "",
  //   total: 0,
  //   sort_by: "",
  //   sort_order: "",
  //   from_date: "",
  //   to_date: "",
  //   order_by: "",
  //   order_type: "",
  // };

  loadUserList() {
    this.adminService.get_users_list().subscribe((res) => {
      this.usersList = res.list;

      // const params = {
      //   search: this.userObj.search,
      //   page: this.userObj.page,
      //   limit: this.userObj.limit,
      // };
      // console.log(this.usersList);
    });
  }

  isToggled: boolean = true;
  showActions = false;
  toggle(id, staus): void {
    staus == "1" ? "0" : "1";
    console.log(staus, "Status");
    console.log(id, "Id");
    console.log(this.usersList);
    this.usersList.forEach((element) => {
      console.log(element);
      if (element.user_id == id) {
        element.is_active == staus;
      }
    });
  }

  onEdit(id: number) {
    // console.log(id);
    this.router.navigate(["/edit-user"], { queryParams: { user_id: id } });
  }

  // onDelete(id: number){
  //   // console.log(id);
  //   this.adminService.delete_user(id).subscribe((res)=>{
  //     if(res.success){
  //       this.toastr.success(res.message);
  //       this.loadUserList();
  //     }else {
  //       this.toastr.error(res.message);
  //     }
  //   });
  //   // this.loadUserList();
  // }

  // delete

  public deleteObj: any = {
    user_id: "",
  };

  //Inquiry Form Submit Funccion
  public isDelete: boolean = false;
  confirmDelete() {
    if (this.isDelete == false) {
      this.isDelete = true;

      this.adminService
        .delete_user(this.deleteObj)
        .subscribe((response: any) => {
          if (response.success == 1) {
            this.deleteObj = {
              user_id: "",
            };
            this.isDelete = false;
            this.loadUserList();
            this.toastr.success(response.message);
          } else {
            this.isDelete = false;
            // this.toastr.error(response.message);
          }
        });

      this.isDelete = false;
    }
  }

  deletepopup(id: any) {
    if (this.deleteObj.user_id == id) {
      this.deleteObj.user_id = "";
    } else {
      this.deleteObj.user_id = id;
    }
  }
  cancelCahnge() {
    this.deleteObj.user_id = "";
  }
}
